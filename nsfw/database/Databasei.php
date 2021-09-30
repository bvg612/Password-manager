<?php

namespace nsfw\database;

use Exception;
use mysqli;
use mysqli_result;

/**
 * This is MySQL database driver. Currently NickSoft Class Library supports only mysql.
 *
 * @author Nikolay Pelov
 */
class Databasei extends AbstractDatabase{

  const MYSQL_USE_RESULT = MYSQLI_USE_RESULT;
  const MYSQL_STORE_RESULT = MYSQLI_STORE_RESULT;

  public $totalTime = 0.0;
  public $timeStart = 0.0;
  private $affectedRows = 0;
  private $numRows = 0;
  public $silent = false;
  public $charset = 'utf8';
  public $reconnect = true;

  public $savedQueries = array();

  protected $newMicrotime = false;
  protected $phpVersionId = 0;
  /** @var mysqli */
  protected $mysqli = null;

  protected $dbHost = null;
  protected $dbName = null;
  protected $dbUser = null;
  protected $dbPass = null;
  protected $dbPort = 3306;
  protected $dbSocket = '';
  protected $dbFlags = 0;
  protected $presistent = false;
  protected $dontClose = false;
  protected $fetchFunc = self::MYSQL_NUM;// MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH.
  protected $transactionSavepoints = array();

  public $quote = '"';

  protected $nextQueryTableInfo = false;
  /*
  array(
    'tables' => array('table1'=>'table1', ...),
    'columnTables' => array('column1'=>'table1', ...),
    'fields' => array(
      'field1' => array(
        'type' => '<field type>',
        'flags' => array('flag1' => 'flag1', ... )
      );
    )
  );
  available flags:
  "not_null", "primary_key", "unique_key", "multiple_key", "blob", "unsigned", "zerofill", "binary", "enum", "auto_increment" and "timestamp".
  field type: "int", "real", "string", "blob", ... others

  */
  protected $tableInfo = null;

  /**
   * Databasei constructor.
   * @param array|string $dbHost
   * @param null $dbName
   * @param null $dbUser
   * @param null $dbPass
   */
  public function __construct($dbHost, $dbName=null, $dbUser=null, $dbPass=null){
    $this->checkPhpVersion();
    if(is_array($dbHost)){
      $this->setHost($dbHost['dbHost']);
      $this->dbName = $dbHost['dbName'];
      $this->dbUser = $dbHost['dbUser'];
      $this->dbPass = $dbHost['dbPass'];
      if(!empty($dbHost['port']))
        $this->dbPort = $dbHost['port'];
      if(!empty($dbHost['socket']))
        $this->dbSocket = $dbHost['socket'];
      if(!empty($dbHost['charset']))
        $this->charset = $dbHost['charset'];
    }else{
      // compatibility - do not use
      if(is_null($dbName) || is_null($dbUser) || is_null($dbPass))
        trigger_error('first parameter must contain array of credentials or all 4 parameters must be set.', E_USER_ERROR);
      $this->setHost($dbHost);
      $this->dbName = $dbName;
      $this->dbUser = $dbUser;
      $this->dbPass = $dbPass;
    }
    $this->timeStart = $this->getmicrotime();
  }

  public function checkPhpVersion(){
    if(!defined('PHP_VERSION_ID')){
      // @codeCoverageIgnoreStart
      $version = explode('.', PHP_VERSION);
      $this->phpVersionId = $version[0] * 10000 + $version[1] * 100 + $version[2];
      // @codeCoverageIgnoreEnd
    }else{
      $this->phpVersionId = PHP_VERSION_ID;
    }
    if($this->phpVersionId >= 50000){
      $this->newMicrotime = true;
    }
  }

  public function setHost($host){
    if(empty($host)) {
      $this->dbHost = null;
      return;
    }

    if(preg_match('/(.+):([0-9]+)/', $host, $m)){
      $this->dbHost = $m[1];
      $this->dbPort = $m[2];
      return;
    }

    if(is_string($host) && $host[0] == ':'){
      $this->dbHost = null;
      $this->dbSocket = substr($host, 1);
      return;
    }

    assert(is_string($host));

    $this->dbHost = $host;
  }

  /**
   * @param bool $value
   */
  public function nextQueryTableInfo($value){
    $this->nextQueryTableInfo = $value;
  }

  public function getTableInfo(){
    return $this->tableInfo;
  }

  public function setEncoding($charset = false){
    if(is_string($charset))
      $this->charset = $charset;

    if(!is_null($this->charset) && !is_null($this->mysqli)){
      $this->lastQuery  ='SET NAMES '.$this->charset;
      if(!$this->mysqli->set_charset($this->charset))
        $this->checkError();
    }
  }

  public function isConnected() {
    return !empty($this->mysqli);
  }


  /**
   * @return mysqli
   * @throws dbException
   */
  public function connect(){
    $this->lastQuery = '-- connect to database';
    $mysqli = mysqli_init();

    $query = 'SET AUTOCOMMIT = 1';
    if (!$mysqli->options(MYSQLI_INIT_COMMAND, $query)) {
      throw new dbException('Setting MYSQLI_INIT_COMMAND failed', 0, null, $query);
    }

    if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
      throw new dbException('Setting MYSQLI_INIT_COMMAND failed', 0, null, '-- set connect timeout');
    }

    //$this->dbFlags
    if(!@$mysqli->real_connect(
      $this->dbHost,
      $this->dbUser,
      $this->dbPass,
      $this->dbName,
      $this->dbPort,
      $this->dbSocket,
      $this->dbFlags
    )) {
      throw new dbException($mysqli->connect_error, $mysqli->connect_errno, null, '-- connect');
    }

    if ($mysqli->connect_errno) {
      throw new dbException("Can't connect to database: ".$mysqli->connect_error, $mysqli->connect_errno, null, '-- connect');
    }
    $this->mysqli = $mysqli;
    $this->setEncoding();


    $this->presistent = false;
    return $this->mysqli;
  }

  /**
   * Import a mysql connection
   *
   * @param mysqli $mysqli - restult from mysql_connect() function
   * @param string|bool $dbName - Database name
   */
  public function import($mysqli, $dbName = false){
    $this->mysqli = $mysqli;

    if($dbName)
      $this->dbName = $dbName;
  }

  /**
   * Selects database
   *
   * @param $dbName - database name
   * @throws dbException
   */
  public function selectDb($dbName){
    if($dbName){
      $this->lastQuery = 'USE '.$dbName;
      if(!@$this->mysqli->select_db($dbName))
        $this->checkError();
      $this->dbName = $dbName;
    }
  }

  /**
   * Use before connection
   *
   * @param int $dbFlags - flags
   *   MYSQLI_CLIENT_COMPRESS	Use compression protocol
   *   MYSQLI_CLIENT_FOUND_ROWS	return number of matched rows, not the number of affected rows
   *   MYSQLI_CLIENT_IGNORE_SPACE	Allow spaces after function names. Makes all function names reserved words.
   *   MYSQLI_CLIENT_INTERACTIVE	Allow interactive_timeout seconds (instead of wait_timeout seconds) of inactivity before closing the connection
   *   MYSQLI_CLIENT_SSL Use SSL (encryption)
   */
  public function setOptions($dbFlags){
    if(!empty($dbFlags))
      $this->dbFlags = $dbFlags;

    if(!empty($this->mysqli)){
      //$this->mysqli->options($option , mixed $value )
      $this->dbFlags = $dbFlags;
    }
  }

  /**
   * @param string $str
   * @param bool $quote
   * @return string
   */
  protected function escapeString($str, $quote = false) {
    $escaped = $this->mysqli->real_escape_string($str);
    if ($quote)
      return $this->quote.$escaped.$this->quote;
    return $escaped;
  }

  /**
   * Returns FOUND_ROWS() from previous query. Must be executed with SQL_CALC_FOUND_ROWS
   *
   * @return int FOUND_ROWS() from previous query.
   * @throws dbException
   */
  public function foundRows(){
    $foundRows = $this->queryFirstField('select FOUND_ROWS() AS found_rows');
    return $foundRows;
  }

  private function retrieveTableInfo(mysqli_result $result) {
    //$tableInfo = &$this->tableInfo;
    $tableInfo = array('tables'=>array(), 'columnTables'=>[],'fields'=>[]);
    //$numFields = $result->field_count;
    $fields = $result->fetch_fields();
    foreach($fields as $field){
      $tableName = $field->table;
      $fieldName = $field->name;
      $fieldType = $field->type;
      $fieldFlags = $field->flags;
      $tableInfo['tables'][$tableName] = $tableName;
      $tableInfo['columnTables'][$fieldName] = $tableName;
      $tableInfo['fields'][$fieldName] = [
        'type' => $fieldType,
        'orgname' => $field->orgname,
      ];
      /* ToDo: fix this mess
      foreach($fieldFlags as $flag){
        $tableInfo['fields'][$fieldName]['flags'][$flag] = $flag;
      }*/
    }// for fields
    return $tableInfo;
  }

  /**
   * Run mysql query
   *
   * field flags:
   *   NOT_NULL_FLAG = 1
   *   PRI_KEY_FLAG = 2
   *   UNIQUE_KEY_FLAG = 4
   *   BLOB_FLAG = 16
   *   UNSIGNED_FLAG = 32
   *   ZEROFILL_FLAG = 64
   *   BINARY_FLAG = 128
   *   ENUM_FLAG = 256
   *   AUTO_INCREMENT_FLAG = 512
   *   TIMESTAMP_FLAG = 1024
   *   SET_FLAG = 2048
   *   NUM_FLAG = 32768
   *   PART_KEY_FLAG = 16384
   *   GROUP_FLAG = 32768
   *   UNIQUE_FLAG = 65536
   *
   * @param string $query - SQL query to run
   * @param int $resultMode - MYSQLI_USE_RESULT or MYSQLI_STORE_RESULT
   *
   * @return bool|mysqli_result MySQL result
   *
   * @throws dbException
   */
  public function query($query, $resultMode = self::MYSQL_STORE_RESULT){
    $errno = 0;
    $error = '';
    $this->tableInfo = null;

    if(empty($this->mysqli)){
      throw new dbException('Must be connected to query', 0, null, $query);
    }

    $queryTimeStart = $this->getmicrotime();
    if($this->reconnect){
      if(!$this->mysqli->ping()){
        $this->checkError();
      }
    }

    // clear error
    $errno = $this->mysqli->errno;
    $error = $this->mysqli->error;

    /** @var mysqli_result|bool $result */
    $result = $this->mysqli->query($query, $resultMode);
    $queryTime = $this->getmicrotime() - $queryTimeStart;
    $this->lastQueryTime = $queryTime;
    $this->totalTime += $this->lastQueryTime;
    $this->lastQuery = $query;
//    $this->savedQueries[]=array('query'=>$query,'time'=>$queryTime);

    if(!$this->checkError())
      return false;

    if($result === false){
      $this->checkError();
      return false;
    }

    $this->numRows = 0;
    $this->affectedRows = 0;
    if($result !== false){
      if($result instanceof mysqli_result)
        $this->numRows = $result->num_rows;
      if($result === true)
        $this->affectedRows = $this->mysqli->affected_rows;

      if(!is_bool($result) && $this->nextQueryTableInfo){
        $this->tableInfo = $this->retrieveTableInfo($result);
      }// if .. next query table info
    }

    return $result;
  }

  public function numRows(){
    return $this->numRows;
  }

  public function affectedRows(){
    return $this->affectedRows;
  }

  /**
   * @param string $fetchFunc
   * @param string $class
   * @throws dbException
   */
  protected function checkClass($fetchFunc, $class) {
    if($fetchFunc == self::MYSQL_OBJECT) {
      if(!class_exists($class, true)) {
        throw new dbException('queryRows(): The class for the row objects "'.$class.'" does not exist.', 0, null, $this->lastQuery);
      }
    }
  }

  public function fetchObject(mysqli_result $result, $class = 'stdClass', $args = []){
    if(method_exists($class, 'importDb') || method_exists($class, 'import')) {
      $row = new $class();
      if(is_callable([$row, 'importDb'])) {
        $arr = $result->fetch_array(self::MYSQL_ASSOC);
        if(empty($arr))
          return null;

        $reflection = new \ReflectionClass($class);
        $row = $reflection->newInstanceArgs($args);

        $row->importDb($arr);
        return $row;
      } else if(is_callable([$row, 'import'])) {
        $arr = $result->fetch_array(self::MYSQL_ASSOC);
        if(empty($arr))
          return null;
        $row = new $class();
        $row->import($arr);
        return $row;
      }
    }

    if(empty($args))
      $row = $result->fetch_object($class);
    else
      $row = $result->fetch_object($class, $args);
    return $row;
  }

  /**
   * Runs query and returns array of values of the first row in the result. If $fetchFunc is MYSQL_ASSOC array is
   * associative using column names as keys.In case of error an exception is thrown.
   *
   * @param string $query
   * @param int $fetchFuncss type of array - self::MYSQL_ASSOC or self::MYSQL_NUM.
   * @param string $class If importDb(array) method exist it'll be called to assign data. If not, look for import(array)
   * method. If not, mysqli_fetch_object() will be used.
   *
   * @return array|bool|object array of values in first row or false if there are no rows
   *
   * @throws dbException
   */
  public function queryFirstRow($query, $fetchFunc = self::MYSQL_ASSOC, $class='stdClass'){

    if(is_null($fetchFunc))
      $fetchFunc = $this->fetchFunc;

    if(is_string($fetchFunc)) {
      $class = $fetchFunc;
      $fetchFunc = self::MYSQL_OBJECT;
    }


    $result = $this->query($query);

    /*
    if(!($result instanceof mysqli_result)) {
      // @codeCoverageIgnoreStart
      throw new dbException('the result of query is not mysqli_result. (it is '.gettype($result).')');
      // @codeCoverageIgnoreEnd
    }
    */

    if($result->num_rows > 0) {
      $this->checkClass($fetchFunc, $class);
    }

    if($fetchFunc == self::MYSQL_OBJECT) {
      $row = $this->fetchObject($result, $class);
    }else {
      $row = $result->fetch_array($fetchFunc);
    }
    mysqli_free_result($result);

    if(is_null($row)) {
      return false;
    }

    return $row;
  }

  protected function emptyRow(mysqli_result $result){
    $row = array();
    $fields = $result->fetch_fields;
    foreach($fields as $field){
      if($field['flags'] && PRI_KEY_FLAG){
        $row[$field['name']] = null;
      }else{
        $row[$field['name']] = $field['def'];
      }
    }
    return $row;
  }

  /**
   * Runs query and returns rows of result as array of arrays. If $fetchFunc is MYSQL_ASSOC array is associative using column names as keys.
   * In case of error an exception is thrown.
   *
   * @param string $query
   * @param int $fetchFunc type of array - self::MYSQL_ASSOC or self::MYSQL_NUM. Values for mysqli extension are MYSQLI_ASSOC or MYSQLI_NUM.
   * @param string $class only used if fetchFunc is MYSQL_OBJECT
   * @return array all rows in the result as array of arrays.
   * @throws dbException
   */
  public function queryRows($query, $fetchFunc = self::MYSQL_ASSOC, $class='stdClass'){
    if(is_null($fetchFunc))
      $fetchFunc = $this->fetchFunc;

    if(is_string($fetchFunc)) {
      $class = $fetchFunc;
      $fetchFunc = self::MYSQL_OBJECT;
    }

    $result = $this->query($query);
    if($result->num_rows > 0) {
      $this->checkClass($fetchFunc, $class);
    }
    $rows=array();
    while(true){
      if($fetchFunc == self::MYSQL_OBJECT) {
        $row = $this->fetchObject($result, $class);
      }else {
        $row = $result->fetch_array($fetchFunc);
      }
      if(is_null($row))
        break;
      $rows[]=$row;
    }
    mysqli_free_result($result);
    return $rows;
  }

  /**
   * @param mysqli_result $result
   * @return array
   */
  protected function getFieldIndexes(mysqli_result $result){
    $fieldsArr = $result->fetch_fields();
    $fields = array();
    $fieldIndexes = array();
    $i=0;
    foreach($fieldsArr as $field){
      $name = $field->name;
      $fields[$i] = $name;
      $fieldIndexes[$name] = $i;
      $i++;
    }
    return array($fields, $fieldIndexes);
  }

  /**
   * @param mysqli_result $result
   * @param $key
   * @param $value
   * @return array
   */
  protected function _fetchAssocByFieldName(mysqli_result $result, $key, $value){
    $fieldCount = $result->field_count;

    list($fields, $fieldIndexes) = $this->getFieldIndexes($result);

    if(!is_null($key) && is_int($key) && array_key_exists($key, $fields)){
      $key = $fields[$key];
    }

    if(is_int($value) && array_key_exists($value, $fields)){
      $value = $fields[$value];
    }

    /*
    if($fieldCount == -11){ // this is disabled
      reset($fields);
      $keyIndex = key($fieldIndexes);
      $valueIndex = $keyIndex;
    }else{
    */
    if(!is_null($key) && !array_key_exists($key, $fieldIndexes))
      trigger_error('nsDatabase::_fetchAssocByFieldIndex(): no field named "'.$key.'"', E_USER_ERROR);
    if(!is_null($value) && !array_key_exists($value, $fieldIndexes)){
      trigger_error('nsDatabase::_fetchAssocByFieldIndex(): no field named "'.$value.'"', E_USER_ERROR);
      //}
    }
    $arr = array();
    while($row = $result->fetch_assoc()){
      if(is_null($value)){
        $valueToAdd = $row;
        if(!is_null($key))
          unset($valueToAdd[$key]);
      }else{
        $valueToAdd = $row[$value];
      }

      if(is_null($key)){
        $arr[] = $valueToAdd;
      }else{
        $arr[$row[$key]] = $valueToAdd;
      }
    }
    return $arr;
  }


  function queryAssoc($sql, $key = null, $value = null){
    $result = $this->query($sql);

    if(!$result)
      return false;

    $arr = $this->_fetchAssocByFieldName($result, $key, $value);

    mysqli_free_result($result);

    return $arr;

  }

  /**
   * Lazy associative array: - first field is the key, second is the value
   * @param string $query
   * @return array
   * @throws dbException
   */
  function queryAssocSimple($query){
    $result = $this->query($query);
    list($fields, $fieldIndexes) = $this->getFieldIndexes($result);
    $value = null;
    reset($fields);
    $key = key($fieldIndexes);
    if(count($fieldIndexes)>1){
      next($fieldIndexes);
      $value = key($fieldIndexes);
    }
    $arr = $this->_fetchAssocByFieldName($result, $key, $value);
    mysqli_free_result($result);
    return $arr;
  }
  /*
protected function fetchObject(array $row, $class = 'stdClass') {

}                      */

  /**
   * @param string $table
   * @param string|array $cond condition fiels in format [ field=>value ] or the where clause as string
   * @param array|string $what
   * @param string $func
   * @param int $fetchFunc
   * @param string $class
   * @return array|string
   * @throws dbException
   */
  public function select($table, $cond = '1', $what = '*', $func = 'queryRows', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass'){
    $fields = '*';
    if(is_array($what)) {
      $fields = '';
      foreach($what as $field) {
        if(!empty($fields))
          $fields .= ',';
        $fields .= $this->escapeField($field);
        if($func == 'queryFirstField')
          break;
      }
    } else if(is_string($what)){
      $fields = $what;
    }

    $where = 1;
    if(is_array($cond)) {
      $where = [];
      foreach($cond as $name => $value) {
        if(is_null($value)) {
          $where[] = $this->escapeField($name) . ' IS NULL';
        } else if(is_array($value)) {
          if (empty($value)) {
            $where = '0';
            break;
          }
          $where[] = $this->escapeField($name) . ' IN ('.implode(',', $this->quote($value)).')';
        } else {
          $where[] = $this->escapeField($name) . ' = ' . $this->quote($value);
        }
        $where = implode(' AND ', $where);
      }
    } else if(is_string($cond)) {
      $where = $cond;
    }

    $query = 'SELECT '. $fields . ' FROM
      '.$this->escapeField($table).'
      WHERE '.$where.'
    ';

    switch($func) {
      case 'queryFirstField':
        return $this->queryFirstField($query);
      case 'queryFirstRow':
        return $this->queryFirstRow($query, $fetchFunc, $class);
      case 'queryRows':
        return $this->queryRows($query, $fetchFunc, $class);
    }

    $result = $this->query($query);
    $rows=array();
    while(true){
      if($fetchFunc == self::MYSQL_OBJECT) {
        $row = $this->fetchObject($result, $class);
      }else {
        $row = $result->fetch_array($fetchFunc);
      }
      if(is_null($row))
        break;
      $rows[]=$row;
    }
    mysqli_free_result($result);
    return $rows;
  }

  /**
   *
   * @param string $table table name
   * @return array result from SHOW COLUMNS FROM <table>
   * @throws dbException
   */
  public function listFields($table){
    return $this->queryRows("SHOW COLUMNS FROM ".$table);
  }

  public function execInsert($table,$fields_arr){
    return $this->insert($table,$fields_arr);
  }


  /**
   * Inserts a row and returns the auto insert id.
   * @param string $table table name
   * @param array $fields_arr
   * @return int insert id
   * @throws dbException
   */
  public function insert($table, array $fields_arr, $ignore = false){
    if(empty($fields_arr))
      return false;
    $query = 'INSERT '.($ignore?' IGNORE':'').' INTO '.$table.' '.$this->prepareInsertFields($fields_arr).' VALUES'.$this->prepareInsert($fields_arr);
    $r = $this->query($query);
    if(!$r)
      return false;
    return $this->insertId();
  }

  /**
   * Replaces a row and returns the auto insert id. Same as insert, but uses REPLACE
   * @param string $table table name
   * @param array $fields_arr
   * @return int insert id
   * @throws dbException
   */
  public function replace($table, array $fields_arr){
    if(empty($fields_arr))
      return false;
    $query = 'REPLACE INTO '.$table.' '.$this->prepareInsertFields($fields_arr).' VALUES'.$this->prepareInsert($fields_arr);
    $r = $this->query($query);
    if(!$r)
      return false;
    return $this->insertId();
  }

  public function multiInsert($table, array $rows, $ignore = false){
    if(empty($rows))
      return 0;
    $firstRow = array_shift($rows);
    $query = $this->prepareInsert($firstRow);
    foreach($rows as $row){
      $query .= ', ' . $this->prepareInsert($row);
    }
    $r = $this->query('INSERT '.($ignore?' IGNORE':'').' INTO ' . $table . $this->prepareInsertFields($firstRow) . ' VALUES ' . $query );
    if(!$r)
      return false;
    return $this->insertId();
  }

  public function multiInsertIgnore($table, $rows){
    return $this->multiInsert($table, $rows, true);
  }

  public function execUpdate($table, array $fields_arr, $where){
    return $this->update($table, $fields_arr, $where);
  }

  /**
   * @param string $table
   * @param array $fields_arr
   * @param string|array $where WHERE clause string OR array of condition fields
   * @return bool|mixed|mysqli_result
   * @throws dbException
   */
  public function update($table, array $fields_arr, $where){
    if (is_array($where)) {
      $where = $this->prepareCondition($where);
    }
    $query='UPDATE '.$table.' SET '.$this->prepareUpdate($fields_arr).' WHERE '.$where;
    return $this->query($query);
  }

  public function execInsertUpdate($table, $fields_arr, $key = 'id', $insertId = true){
    return $this->insertUpdate($table, $fields_arr, $key, $insertId);
  }

  public function insertUpdate($table, $fields_arr, $key = 'id', $insertId = true){
    if(!empty($key)) {
      if(array_key_exists($key, $fields_arr) && is_null($fields_arr[$key]))
        unset($fields_arr[$key]);
    }

    $updateArr = $fields_arr;

    $query = 'INSERT INTO '.$table.' '.$this->prepareInsertFields($fields_arr).' VALUES'.$this->prepareInsert($fields_arr).
      ' ON DUPLICATE KEY UPDATE '.
      ($insertId?' '.$key.' = LAST_INSERT_ID('.$key.'),':'').
      $this->prepareUpdate($updateArr);
    $this->query($query);
    if($insertId)
      return $this->insertId();
    if($key)
      return $fields_arr[$key];
    return null;
  }

  /**
   * @param string $table
   * @param array $fields_arr
   * @param string|array |null $condField
   * @return mixed|void
   * @throws Exception
   * @throws dbException
   */
  public function simpleUpdate($table, array $fields_arr, $condField = null){
    if(empty($condField)){
      reset($fields_arr);
      $condField = key($fields_arr);
    }

//    $where = $this->prepareCondition($condField);


    if(is_array($condField)) {
      // remove the fields from updating. WHY?
      $conditions = [];
      foreach($condField as $singleField) {
        $conditions[$singleField] = $fields_arr[$singleField];
        if(isset($fields_arr[$singleField]))
          unset($fields_arr[$singleField]);
      }
      $where = $this->prepareCondition($conditions);
    }else if(is_string($condField)) {
      $where = $this->prepareCondition([$condField=>$fields_arr[$condField]]);
      if(isset($fields_arr[$condField]))
        unset($fields_arr[$condField]);
    }else {
      //@codeCoverageIgnoreStart
      throw new Exception('Third paramter must be string or array when present');
      //@codeCoverageIgnoreEnd
    }
    $query='UPDATE '.$table.' SET '.$this->prepareUpdate($fields_arr).' WHERE '.$where;
    $this->query($query);
  }


  public function escapeField($field){
    if(is_string($field) && preg_match('/^[a-z_][a-z0-9\._]*$/i',$field)) {
      return '`' . str_replace('.', '`.`', $field) . '`';
    }else {
      return $field;
    }
  }

  public function insertId(){
    return $this->mysqli->insert_id;
  }

  public function _startTransaction($snapshot = false, $name = null){

    $addon = '';
    if($snapshot)
      $addon = ' WITH CONSISTENT SNAPSHOT';

    @$this->mysqli->query($this->lastQuery = 'START TRANSACTION'.$addon);
    $this->checkError();
  }

  public function restartTransaction($snapshot = false){
    $this->commit();
    $this->_startTransaction($snapshot);
    $this->transactionSavepoints = array(null);
  }

  public function startTransaction($snapshot = false){
    $savePoints = count($this->transactionSavepoints);
    $addon = '';

    if($savePoints == 0){
      array_push($this->transactionSavepoints, null);
      $this->_startTransaction($snapshot);
    }else{
      $this->savepoint('AUTO_SAVE_'.$savePoints);
    }

  }

  public function savepoint($savepoint){
    array_push($this->transactionSavepoints, $savepoint);
    $this->mysqli->query($this->lastQuery = 'SAVEPOINT '.$this->escapeField($savepoint));
    $this->checkError();
  }

  public function releaseSavepoint($savepoint){
    $this->mysqli->query($this->lastQuery = 'RELEASE SAVEPOINT '.$this->escapeField($savepoint));
    $this->checkError();
  }

  public function commit(){
    $this->mysqli->query($this->lastQuery = 'COMMIT');
    $this->checkError();
  }

  public function commitOne(){
    $savepoint = array_pop($this->transactionSavepoints);
    if(is_null($savepoint)){
      $this->commit();
    }else{
      $this->releaseSavepoint($savepoint);
    }
  }

  public function rollbackSavepoint(){
    $autoSavepoint = array_pop($this->transactionSavepoints);
    if(is_null($autoSavepoint)){//is first savepoint (start transaction)?
      // yes - rollback without savepoint
      $this->mysqli->query($this->lastQuery = 'ROLLBACK');
      $this->checkError();
    }else{
      $this->mysqli->query($this->lastQuery = 'ROLLBACK TO SAVEPOINT '.
        $this->escapeField($autoSavepoint));
      $this->checkError();
    }
  }

  public function rollback($savepoint = null){
    if(!is_null($savepoint)) {
      $this->mysqli->query($this->lastQuery = 'ROLLBACK TO SAVEPOINT '.
        $this->escapeField($savepoint));
      $this->checkError();
      return;
    }
    $this->mysqli->query($this->lastQuery = 'ROLLBACK');
    $this->checkError();
    $this->transactionSavepoints = array();
  }

  protected function checkError($sureError = false){
    $mysqlErrno = $this->mysqli->errno;
    $mysqlError = $this->mysqli->error;
    if($mysqlErrno > 0 || $sureError){
      throw new dbException($mysqlError, $mysqlErrno, null, $this->lastQuery);
    }
    return true;
  }

  protected function getmicrotime(){
    if($this->newMicrotime)
      return microtime(true);

    // @codeCoverageIgnoreStart
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    // @codeCoverageIgnoreEnd
  }

  public function close(){
    if(is_null($this->mysqli))
      return;
    $this->mysqli->close();
    $this->mysqli = null;
  }

  // mysql functions for use with php

  function CURDATE($add = ''){
    return $this->queryFirstField('SELECT CURDATE() '.$add);
  }

  function CURTIME($add = ''){
    return $this->queryFirstField('SELECT CURTIME()'.$add);
  }

  function NOW($add = ''){
    return $this->queryFirstField('SELECT NOW()'.$add);
  }

  function DATE_CALC($date, $add){
    return $this->queryFirstField('SELECT "'.$this->escape(trim($date)).'" '.$add);
  }

  function UNIX_TIMESTAMP($date = null){
    if(is_null($date))
      return $this->queryFirstField('SELECT UNIX_TIMESTAMP()');
    $ts = $this->queryFirstField('SELECT UNIX_TIMESTAMP("'.$this->escape($date).'")');
    return $ts;
  }

  function FROM_UNIXTIME($d){
    return $this->queryFirstField('SELECT FROM_UNIXTIME("'.$this->escape($d).'")');
  }

  public function FROM_UNIXTIME_DATE($d){
    return $this->queryFirstField('SELECT DATE(FROM_UNIXTIME("'.$this->escape($d).'"))');
  }

  /**
   * Same as UNIX_TIMESTAMP, but does not use mysql server (done in php)
   *
   * @param string $date
   * @return bool|int
   */
  public static function mysqlToTimestamp($date){
    if(preg_match('/^\\s*([1-2][0-9]{3})-([0-1]?[0-9])-([0-3]?[0-9])\\s+([0-2]?[0-9]):([0-5]?[0-9]):([0-5]?[0-9])\\s*$/', $date, $m))
      return mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);

    if(preg_match('/^\\s*([1-2][0-9]{3})-([0-1]?[0-9])-([0-3]?[0-9])\\s*$/', $date, $m))
      return mktime(0, 0, 0, $m[2], $m[3], $m[1]);

    return false;
  }

  public static function dateDDMMYYYYToMysql($date){
    if(preg_match('/([0-3][0-9])[\.-]([0-1][0-9])[\.-]([1-2][0-9]{3})/', $date, $m))
      return $m[3].'-'.$m[2].'-'.$m[1];

    return false;
  }

  function setTimezone($tz){
    $this->query('SET time_zone = "'.$this->escape($tz).'"');
    return true;
  }

  function getTimezone(){
    return $this->queryFirstField('SELECT @@session.time_zone AS tz');
  }

  function CONVERT_TZ($time, $fromTz, $toTz){
    if($fromTz == $toTz)
      return $time;
    return $this->queryFirstField('SELECT CONVERT_TZ("'.$this->escape($time).'","'.$this->escape($fromTz).'","'.$this->escape($toTz).'")');
  }

  function DATE_FORMAT($date, $format){
    $date = $this->queryFirstField('SELECT DATE_FORMAT("'.$this->escape($date).'", "'.$this->escape($format).'")');
    if(empty($date))
      return false;
    return $date;
  }

  // no escaping. Use with functions like NOW() and DATE_SUB()
  function DATE_FORMAT_RAW($date, $format){
    $date = $this->queryFirstField('SELECT DATE_FORMAT('.$date.', "'.$this->escape($format).'")');
    if(empty($date))
      return false;
    return $date;
  }

  function DATE_FORMAT_TS($timestamp, $format){
    $q = 'SELECT DATE_FORMAT(FROM_UNIXTIME("'.$this->escape($timestamp).'"), "'.$this->escape($format).'")';
    if(!is_numeric($timestamp))
      throw new dbException('timestamp "'.$timestamp.'" must be a valid unix timestamp', 1064, null, $q);
    $formatted = $this->queryFirstField($q);
    return $formatted;
  }

  public function __destruct(){
    if(!$this->dontClose){
      $this->close();
    }
  }
} // Databasei
