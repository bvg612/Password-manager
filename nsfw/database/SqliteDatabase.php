<?php
/**
 * User: npelov
 * Date: 11-11-17
 * Time: 12:03 PM
 */

namespace nsfw\database;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;

/**
 * Class SqliteDatabase
 *
 * Requires Sqlite3 extension
 *
 * @package nsfw\database
 *
 */
class SqliteDatabase extends AbstractDatabase {
  const RESULT_ASSOC = SQLITE3_ASSOC;
  const RESULT_NUM = SQLITE3_NUM;
  const RESULT_BOTH = SQLITE3_BOTH;

  /** @var SQLite3 */
  protected $sqlite;
  /** @var string */
  protected $filename;
  protected $flags;
  /** @var array */
  protected $transactionSavepoints = [];

  /**
   * SqliteDatabase constructor.
   * @param string $filename
   * @param $flags
   */
  public function __construct($filename, $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE) {
    $this->quote = '"';
    $this->filename = $filename;
    $this->flags = $flags;
  }

  public function isConnected() {
    return is_null($this->sqlite);
  }


  public function setFlags($flags) {
    $this->flags = $flags;
  }

  /**
   * @return int
   */
  public function getFlags() {
    return $this->flags;
  }

   public function connect() {
     if(!is_file($this->filename)) {
       throw new dbException('Could not find database file "'.$this->filename.'"');
     }
     $link = $this->sqlite = new SQLite3($this->filename, $this->flags);
   }

  /**
   * @param string $str
   * @param bool $quote
   * @return string
   */
  protected function escapeString($str, $quote = false) {
    $escaped = SQLite3::escapeString($str);
    if ($quote)
      return $this->quote.$escaped.$this->quote;
    return $escaped;
  }


  public function escapeField($field) {
    if(strpos($field, '"') !== false)
      $field = '"'.$field.'"';
    return $field;
  }

  /**
   * @param string $query
   * @param array $preparedFields
   * @throws dbException
   */
  public function query($query){
    if(!empty($preparedFields))
      $this->queryPrepared($query, $preparedFields);
    $db = $this->sqlite;
    if(!@$db->exec($query)){
      throw new dbException($db->lastErrorMsg(), $db->lastErrorCode(), null, $query);
    }
  }

  /**
   * @param $query
   * @param $fields
   * @return bool
   * @throws dbException
   */
  public function queryPrepared($query, $fields){
    $stmt = $this->createStatement($query, $fields);

    $result = $stmt->execute();
    $stmt->close();
    return false;
  }

  /**
   * @param string $sql
   * @param $fields
   *
   * @return SqliteStatement The statement. Must be closed unsing SQLite3Stmt::close();
   * @throws dbException
   */
  public function createStatement($sql, array $fields = []) {
    $db = $this->sqlite;
    $this->lastQuery = $sql;
    $stmt = new SqliteStatement($this, $sql, $fields);
    return $stmt;

  }

  public function startTransaction($type = 'IMMEDIATE'){
    $savePoints = count($this->transactionSavepoints);
    if($savePoints == 0){
      array_push($this->transactionSavepoints, 'MAIN_TRANSACTION');
      $this->sqlite->query("BEGIN ".$type." TRANSACTION");
    }else{
      $this->savepoint('AUTO_SAVE_'.$savePoints);
    }

  }

  /**
   * @param string $name
   */
  public function savepoint($name){
    array_push($this->transactionSavepoints, $name);
    $this->sqlite->query('SAVEPOINT "'.$name.'"');
  }

  /**
   * @param string $name
   * @throws dbException
   */
  public function releaseSavepoint($name = null){
    if(empty($this->transactionSavepoints)) {
      $this->commit();
    }

    if(is_null($name)) {
      $name = end($this->transactionSavepoints);
      $savepointIndex = key($this->transactionSavepoints);
    }else {
      $savepointIndex = array_search($name, $this->transactionSavepoints);
      if($savepointIndex === false)
        throw new dbException('Savepoint ' . $name . ' not found', 0, null, ' -- release savepoint');
    }
    $transReverse = array_reverse($this->transactionSavepoints);
    foreach($transReverse as $key=>$savepoint) {
      unset($this->transactionSavepoints[$key]);
      if($key == $savepointIndex) {
        break;
      }
    }
    if($name == 'MAIN_TRANSACTION') {
      $this->commit();
    }else {
      $this->sqlite->query('RELEASE "' . $name . '"');
    }
  }

  public function commitOne() {
    $this->releaseSavepoint();
  }

  public function rollbackSavepoint($savepoint = null) {
    if(empty($savepoint)) {
      $savepoint = end($this->transactionSavepoints);
      if($savepoint == false) {
        $this->_rollbackSavepoint();
      }else{
        $savepointIndex = key($this->transactionSavepoints);
      }
    }

    if(!isset($savepointIndex)) {
      $savepointIndex = array_search($savepoint, $this->transactionSavepoints);
    }
    if($savepointIndex === false)
      throw new dbException('Savepoint '.$savepoint.' not found', 0, null, ' -- release savepoint');
    $transReverse = array_reverse($this->transactionSavepoints);
    foreach($transReverse as $key=>$currentSavepoint) {
      unset($this->transactionSavepoints[$key]);
      if($currentSavepoint == $savepoint) {
        break;
      }
    }

    if($savepoint == 'MAIN_TRANSACTION')
      $this->sqlite->query("ROLLBACK");
    else
      $this->_rollbackSavepoint($savepoint);
  }

  public function commit(){
    $this->sqlite->query("COMMIT");
    $this->transactionSavepoints = [];
  }


  /**
   * Rolls back transaction or safepoint. Does not change $this->transactionSavepoints. For internal use only
   * @param $savepoint
   */
  private function _rollbackSavepoint($savepoint = 'MAIN_TRANSACTION') {
    if($savepoint == 'MAIN_TRANSACTION') {
      $this->sqlite->query("ROLLBACK");
      return;
    }
    $this->sqlite->query('ROLLBACK TO SAVEPOINT "'.$savepoint.'"');
  }

  public function rollback($savepoint = false){
    if($savepoint !== false) {
      $this->rollbackSavepoint($savepoint);
      //$this->sqlite->query('ROLLBACK TO SAVEPOINT "' . $savepoint . '"');
    } else {
      if(empty($this->transactionSavepoints)) {
        $this->_rollbackSavepoint();
      }else {
        $this->rollbackSavepoint();
      }
    }
  }

  public function rollbackAll() {
    $this->transactionSavepoints = [];
    $this->sqlite->query("ROLLBACK");
  }

  public function affectedRows(){
    return $this->sqlite->changes();
  }

  /**
   * @param string $query
   * @param array $fields
   * @return SqliteStatement
   * @throws dbException
   */
  public function queryResult($query, array $fields){
    $stmt = $this->createStatement($query, $fields);
    $result = $stmt->execute();
    return $stmt;
  }


  /**
   * @param string $table
   * @param array $fields
   * @param bool $ignore
   * @return int
   * @throws dbException
   */
  public function insert($table, array $fields, $ignore = false){
    $db = $this->sqlite;
    $sql = 'INSERT ';
    if($ignore)
      $sql .= ' OR IGNORE ';
    $sql .= 'INTO '.$table.'('.implode(',', array_keys($fields)).') VALUES(';
    $first = true;
    foreach($fields as $fieldName=>$value){
      if($first){
        $first = false;
      }else{
        $sql .= ',';
      }
      $sql .= ':'.$fieldName;
    }
    $sql.=')';
    if($ignore)
      $sql .= ' ON CONFLICT IGNORE ';
    $this->lastQuery = $sql;
    $stmt = $this->createStatement($sql, $fields);
    //echo "executing insert with fields: ".print_r($fields, true)."\n";
    $stmt->execute();
    $stmt->close();
    //var_dump($result->fetchArray(SQLITE3_ASSOC));
    //$result->finalize();
    return $this->insertId();
  }

  /**
   * @param $table
   * @param array $fields
   * @param array|string|null $whereFields one of:
   *        null - first field of $fields is taken
   *        string value - the key of the field to be taken from $fields
   *        array - multiple keys from $fields will be used for WHERE clause
   * @throws dbException
   */
  public function simpleUpdate($table, array $fields, $whereFields = null){
    $db = $this->sqlite;
    if(is_null($whereFields)){
      reset($fields);
      $whereFields = [ key($fields) ];
    }
    if(is_string($whereFields)){
      $whereFields = [ $whereFields ];
    }
    //whereField, whereValue
    $sql = 'UPDATE '.$table.' SET ';
    $whereClauses = [];
    $whereValues = [];
    foreach($whereFields as $whereField ){
      $whereValues['WHERE_'.$whereField] = $fields[$whereField];
      unset($fields[$whereField]);
      $whereClauses[] = $whereField . ' = :WHERE_'.$whereField;
    }

    $fieldStatements = [];
    foreach($fields as $field=>$value){
      $fieldStatements[] = $field.'= :'.$field;
    }

    $sql .= implode(',', $fieldStatements);
    $sql .= ' WHERE '.implode(' AND ', $whereClauses);


    $this->lastQuery = $sql;
    $stmt = $this->createStatement($sql);

    if(empty($stmt)){
      throw new dbException($db->lastErrorMsg(), $db->lastErrorCode(), null, $sql);
    }

    $stmt->bind($fields);
    $stmt->bind($whereValues);

    $result = $stmt->execute();

    $stmt->close();
  }

  /**
   * @param string $sql
   * @param array $fields
   * @param int $fetchFunc
   * @return array
   * @throws dbException
   */
  public function queryRows($sql, $fields = [], $fetchFunc = self::RESULT_ASSOC){
    $db = $this->sqlite;
    $this->lastQuery = $sql;

    $stmt = $this->createStatement($sql, $fields);
    $result = $stmt->execute();
    $rows = [];
    while($row = $result->fetchArray($fetchFunc)){
      $rows[] = $row;
    }

    $stmt->close();
    return $rows;
  }

  /**
   * @param string $sql
   * @param array $fields
   * @param int|string $fetchFunc
   * @return array|bool|mixed|object
   * @throws dbException
   */
  public function queryFirstRow($sql, $fields = [], $fetchFunc = self::RESULT_ASSOC){
    $rows = $this->queryRows($sql, $fields, $fetchFunc);
    if(empty($rows))
      return false;
    return $rows[0];
  }

  /**
   * @param string $sql
   * @param array $fields
   * @param int $fetchFunc
   * @return bool|string
   * @throws dbException
   */
  public function queryFirstField($sql, $fields = [], $fetchFunc = self::RESULT_ASSOC){
    $row = $this->queryFirstRow($sql, $fields, $fetchFunc);
    if(empty($row))
      return false;
    return reset($row);
  }

  /**
   * @param string $charset
   * @throws dbException
   */
  public function setEncoding($charset) {
    if(strtolower($charset) == 'utf8')
      $charset = 'UTF-8';
    $this->query('pragma encoding="'.$charset.'"');
  }

  /**
   * @param SQLite3 $connectionResource
   * @param bool $dbName not used in sqlite 3
   */
  public function import($connectionResource, $dbName = false) {
    $this->sqlite = $connectionResource;
  }

  /**
   * @return SQLite3
   */
  public function getLink() {
    return $this->sqlite;
  }

  /**
   * Not used in sqlite
   * @param string $dbName
   */
  public function selectDb($dbName) {}

  /**
   * @param $table
   * @param string $cond
   * @param string $what
   * @param string $func
   * @param int $fetchFunc
   * @param string $class
   * @return array|bool|mixed|object|string
   * @throws \Exception
   * @throws dbException
   */
  public function select($table, $cond = '1', $what = '*', $func = 'queryRows', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass') {
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
    $whereFields = [];
    if(is_array($cond)) {
      $whereFields = [];
      $where = '';
      foreach($cond as $name => $value) {
        if(is_string($value) || is_int($value)) {
          $where .= $this->escapeField($name) . ' = :' . $name . ' ';
          $whereFields[$name] = $value;
        } else if(is_null($value)){
          $where .= $this->escapeField($name) . ' IS NULL';
          unset($whereFields[$name]);
        } else if(is_bool($value)){
          $where .= $this->escapeField($name) . ' = ' . ($value?'1':'0') ;
        }
      }
    } else if(is_string($cond)) {
      $where = $cond;
    } else {
      throw new dbException('Wrong type '.gettype($cond).' for $cond parameter. Allowed types: array, string', 0, '-- select()');
    }

    $sql = 'SELECT '. $fields . ' FROM
      '.$this->escapeField($table).'
      WHERE '.$where.'
    ';

    switch($func) {
      case 'queryFirstField':
        return $this->queryFirstField($sql, $whereFields);
      case 'queryFirstRow':
        return $this->queryFirstRow($sql, $whereFields);
      case 'queryRows':
        return $this->queryRows($sql, $whereFields);
    }
    throw new \Exception('Bad query function '.$func);
  }

  /**
   * @param SqliteStatement $stmt
   * @param string|null $key
   * @param string|null $value
   * @return array
   * @throws dbException
   */
  private function _fetchAssocByFieldName(SqliteStatement $stmt, $key, $value) {
    $result = $stmt->getResult();
    list($fields, $fieldIndexes) = $stmt->getFieldIndexes();
    if(!is_null($key) && !array_key_exists($key, $fieldIndexes))
      throw new dbException('SqliteDatabase::_fetchAssocByFieldIndex(): no field named "'.$key.'"', 0, null, '-- fetch assoc');
    if(!is_null($value) && !array_key_exists($value, $fieldIndexes))
      throw new dbException('SqliteDatabase::_fetchAssocByFieldIndex(): no field named "'.$value.'"', 0, null, '-- fetch assoc');

    $rows = [];
    while($row = $result->fetchArray(self::RESULT_ASSOC)){
      if(is_null($value)){
        $valueToAdd = $row;
        if(!is_null($key))
          unset($valueToAdd[$key]);
      }else{
        $valueToAdd = $row[$value];
      }

      if(is_null($key)){
        $rows[] = $valueToAdd;
      }else{
        $rows[$row[$key]] = $valueToAdd;
      }
    }

    return $rows;
  }

  /**
   * @param string $sql
   * @param array $fields
   * @param null $key
   * @param null $value
   * @return array|false
   * @throws dbException
   */
  function queryAssoc($sql, $fields = [], $key = null, $value = null) {
    $stmt = $this->createStatement($sql, $fields);
    $stmt->execute();
    if(!$stmt->getResult())
      return false;
    $arr = $this->_fetchAssocByFieldName($stmt, $key, $value);
    $stmt->close();
    return $arr;
  }

  /**
   * @param string $sql
   * @param array $fields
   * @return array|bool
   * @throws dbException
   */
  function queryAssocSimple($sql, $fields = []) {
    $stmt = $this->createStatement($sql, $fields);
    $stmt->execute();
    if(!$stmt->getResult())
      return false;

    list($fields, $fieldIndexes) = $stmt->getFieldIndexes();
    $value = null;
    reset($fields);
    $key = key($fieldIndexes);
    if(count($fieldIndexes)>1){
      next($fieldIndexes);
      $value = key($fieldIndexes);
    }
    $arr = $this->_fetchAssocByFieldName($stmt, $key, $value);

    $stmt->close();

    return $arr;
  }

  /**
   * @param string $table
   * @param array $rows
   * @param bool $ignore
   * @return bool|int|mixed
   * @throws dbException
   */
  public function multiInsert($table, array $rows, $ignore = false) {
    $db = $this->sqlite;
    if(empty($rows))
      return 0;

    $firstRow = reset($rows);

    $sql = 'INSERT ';
    if($ignore)
      $sql .= ' OR IGNORE ';
    $sql .= ' INTO '.$table.'('.implode(',', array_keys($firstRow)).') VALUES(';

    $first = true;
    foreach($firstRow as $fieldName=>$value){
      if($first){
        $first = false;
      }else{
        $sql .= ',';
      }
      $sql .= ':'.$fieldName;
    }
    $sql.=')';
    if($ignore)
      $sql .= ' ON CONFLICT IGNORE ';
    $this->lastQuery = $sql;
    $stmt = $this->createStatement($sql);
    //echo "executing insert with fields: ".print_r($fields, true)."\n";
    foreach($rows as $row) {
      $result = $stmt->execute($row);
      $result->finalize();
    }
    $stmt->close();
    //var_dump($result->fetchArray(SQLITE3_ASSOC));
    //$result->finalize();
    return $db->lastInsertRowID();
  }

  public function update($table, array $fields_arr, $where) {
    // TODO: Implement update() method.
  }

  public function insertUpdate($table, $fields_arr, $key = 'id', $insertId = true) {
    // TODO: Implement insertUpdate() method.
  }

  public function insertId() {
    return $this->sqlite->lastInsertRowID();
  }

  public function setTimezone($tz) {
    ini_set('date.timezone', $tz);
  }

  public function getTimezone() {
    ini_get('date.timezone');
  }

  public function close() {
    if(empty($this->sqlite))
      return;
    $this->sqlite->close();
    $this->sqlite = null;
  }

}
