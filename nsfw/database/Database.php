<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 30.04.2016
 * Time: 5:38 PM
 */

namespace nsfw\database;


interface Database {
  const MYSQL_ASSOC = 1;
  const MYSQL_NUM = 2;
  const MYSQL_BOTH = 3;
  const MYSQL_OBJECT = 4;

  /**
   * Database constructor.
   * @param string|array $dbHost host in format host.name:port or array of all db config (see below)
   * @param string|null $dbName Database name. Can be empty to not USE database;
   * @param string|null $dbUser database username
   * @param string|null $dbPass password
   */
  //public function __construct($dbHost, $dbName = null, $dbUser = null, $dbPass = null);

  /**
   * @return string
   */
  public function getLastQuery();

  /**
   * @param string$charset
   */
  public function setEncoding($charset);

  /**
   * Returns true if connected, false otherwise
   * @return bool
   */
  public function isConnected();

  /**
   * Connects to (or opens) database using credentials supplied previously
   */
  public function connect();

  /**
   * @param mixed $connectionResource Driver dependant connection resource
   * @param string|bool $dbName
   */
  public function import($connectionResource, $dbName = false);

  /**
   * Selects database
   * @param string $dbName database name
   */
  public function selectDb($dbName);

  public function affectedRows();

  /**
   * @param string $query SQL query to run
   * @param $resultMode - MYSQLI_USE_RESULT or MYSQLI_STORE_RESULT
   * @return mixed Driver dependant resource
   *
   * @ToDo: needs driver independant mode constants
   */
  public function query($query);

  /**
   * Runs query and returns value of first field of first row of the result. In case of error an exception is thrown.
   *
   * @param string $query
   * @return mixed One single value of the first field of the first row.
   * @throws dbException
   */
  public function queryFirstField($query);

  /**
   * Runs query and returns array of values of the first row in the result. If $fetchFunc is MYSQL_ASSOC array is associative using column names as keys.
   *
   * @param string $query
   * @param int $fetchFunc type of array - static::MYSQL_ASSOC or static::MYSQL_NUM.
   * @return array|object A single row as array.
   *
   * @throws dbException
   */
  public function queryFirstRow($query, $fetchFunc = self::MYSQL_ASSOC, $class='stdClass');

  /**
   * Returns empty row that matches the result. For now it's optional
   */
  //protected function emptyRow(mysqli_result $result);

  /**
   * Runs query and returns rows of result as array of arrays. If $fetchFunc is MYSQL_ASSOC array is associative using column names as keys.
   *
   * @param string $query
   * @param int $fetchFunc
   * @return array All rows in the result as array of arrays.
   *
   * @throws dbException
   */
  public function queryRows($query, $fetchFunc = self::MYSQL_ASSOC, $class='stdClass');


  /**
   * @param $table
   * @param string $cond
   * @param array|string $what
   * @param string $func
   * @param int $fetchFunc
   * @param string $class
   * @return mixed
   */
  public function select($table, $cond = '1', $what = '*', $func = 'queryRows', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass');

  /**
   * @param $table
   * @param int $cond
   * @param string $what
   * @return mixed
   */
  public function selectFirstField($table, $cond = 1, $what = '*');

  /**
   * @param $table
   * @param int $cond
   * @param string $what
   * @param int $fetchFunc
   * @param string $class
   * @return mixed
   */
  public function selectFirstRow($table, $cond = 1, $what = '*', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass');

  /**
   * @param $table
   * @param int $cond
   * @param string $what
   * @param int $fetchFunc
   * @param string $class
   * @return mixed
   */
  public function selectRows($table, $cond = 1, $what = '*', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass');


  /**
   *
   * Examples:
   * if the query returns these fields:
   *   'id', 'var', 'value'
   *  and 2 rows:
   *  [
   *    ['1', 'var1' ,'value1'],
   *    ['2', 'var2' ,'value2'],
   *  ]
   * queryAssoc($query) - returns ['1'=> ['id'=>'1', 'var'=>'var1', 'value'=>'value1'], '2'=> ['id'=>'2', 'var'=>'var2', 'value'=>'value2'] ]
   * queryAssoc($query, 'id') - returns ['1'=> ['var'=>'var1', 'value'=>'value1'], '2'=> ['var'=>'var2', 'value'=>'value2'] ]
   * queryAssoc($query, 'var', 'value') - returns ['var1'=> 'value1', 'var2'=> 'value2' ]
   *
   * @param string $sql
   * @param string $key The name of the field used for array key. If this is null, the first field will be used as key.
   * @param string $value The name of the field used for array value. If empty the whole row is the value.
   * In this case the row will not include $key element unless $key param is null
   *
   * @return array associative array where key is value of $key field and value id value of $value field.
   *
   *
   */
  function queryAssoc($sql, $key = null, $value = null);

  /**
   * Lazy associative array: - first field is the key, second is the value
   *
   * @param string $query
   * @return array
   */
  function queryAssocSimple($query);

  /**
   * @param $table
   * @return mixed
   *
   * @ToDo: Standartize list fields, so that it returns the same result with all drivers
   */
  //public function listFields($table);


  /**
   * @param array $fields_arr
   * @return string
   */
  public function prepareUpdate(array $fields_arr);

  /**
   * Inserts a row and returns the auto insert id.
   * @param string $table table name
   * @param array $fields_arr
   * @return int insert id
   * @throws dbException
   */
  public function insert($table, array $fields_arr, $ignore = false);

  /**
   * @param string $table
   * @param array $fields_arr
   * @return int insert id
   * @throws dbException
   */
  public function insertIgnore($table, array $fields_arr);


  /**
   * @param string $table table name
   * @param array $rows
   * @param bool $ignore
   * @return mixed
   * @throws dbException
   */
  public function multiInsert($table, array $rows, $ignore = false);

  /**
   * @param string $table
   * @param array $fields_arr
   * @param string $where
   * @return mixed response of query()
   * @throws dbException
   */
  public function update($table, array $fields_arr, $where);

  /**
   * @param string$table
   * @param array $fields_arr
   * @param string $key
   * @param bool $insertId
   * @return mixed
   * @throws dbException
   */
  public function insertUpdate($table, $fields_arr, $key = 'id', $insertId = true);

  /**
   * @param string $table
   * @param array $fields_arr
   * @param string|array|null $condField string containing which field from $fields array will be taken as condition.
   *   It can also be an array of fields
   * @return mixed
   * @throws dbException
   */
  public function simpleUpdate($table, array $fields_arr, $condField = null);

  /**
   * Should change names that match keys of $nameTranslation to the values of it.
   *
   * @param array $data Data to be translated
   * @param array $nameTranslation names in format ['previous name'=> 'new name']
   * @return array
   */
  public function translateData(array $data, array $nameTranslation);

  /**
   * Escapes a string to use in query
   *
   * @param string|int|bool|object|array $value string to escape
   * @param bool $quote True if the value should be quoted (put quotes around it if needed)
   * @return string|array
   */
  public function escape($value, $quote = false);


  /**
   * @param string|int|bool|object|array $value
   * @return string|array
   */
  public function quote($value);

  /**
   * @param string $field
   * @return string
   */
  public function escapeField($field);

    /**
   * @param array $list
   * @return string
   */
  public function escapeStringList(array $list);


  public function insertId();

  public function startTransaction($snapshot = false);

  public function savepoint($savepoint);
  public function releaseSavepoint($savepoint);
  public function commitOne();
  public function commit();
  public function rollback($savepoint = null);

  /**
   * Rollbacks auto savepoint
   */
  public function rollbackSavepoint();


  public function setTimezone($tz);
  public function getTimezone();

  /**
   * @param string $time Time in mysql format YYYY-MM-DD hh-mm-ss
   * @param string $fromTz Source timezone
   * @param string $toTz Destination timezone
   * @return string Converted time in mysql format
   */
  //function CONVERT_TZ($time, $fromTz, $toTz);

  public function close();
}
