<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 03-05-16
 * Time: 10:48 AM
 */

namespace nsfw\database;


abstract class AbstractDatabase implements Database{
  /** @var float */
  public $lastQueryTime = 0.0;
  /** @var string */
  public $lastQuery;
  /** @var string Which quote to use in queries */
  public $quote = "'";


  /**
   * @return string
   */
  public function getLastQuery() {
    return $this->lastQuery;
  }

  /**
   * Runs query and returns value of first field of first row of the result. In case of error an exception is thrown.
   *
   * @param string $query a query to run.
   * @return string value of first field of first row of the result
   * @throws dbException
   */
  public function queryFirstField($query){
    $row = $this->queryFirstRow($query, MYSQLI_NUM);
    if(!$row)
      return false;
    else
      return reset($row);
  }

  public function translateData(array $data, array $nameTranslation, $reverse = false) {
    if($reverse)
      $nameTranslation = array_flip($nameTranslation);

    $newData = [];
    foreach($data as $key=>$value) {
      if(!empty($nameTranslation[$key]))
        $newData[$nameTranslation[$key]] = $value;
      else
        $newData[$key] = $value;
    }
    return $newData;
  }

  abstract protected function escapeString($str, $quote = false);

  /**
   * Escapes single value. Override this if the database engine can't accept integer without quotes.
   *
   * @param string|int|bool|object $value
   * @param bool $quote
   * @return int|string
   * @throws dbException
   */
  protected function escapeSingleValue($value, $quote = false) {
    $this->lastQuery = '-- real escape string';
    if(is_string($value)) {
      return $this->escapeString($value, $quote);
    } else if(is_int($value)){
      return intval($value);
    } else if(is_null($value)){
      return 'NULL';
    } else if(is_bool($value)){
      return $value?'1':'0';
    } else if (is_object($value)){
      if(method_exists($value, '__toString'))
        return $this->escapeString($value, $quote);
      else
        throw new dbException('Cannot convert object of class "' . get_class($value) . '" to string');
    }

    throw new dbException('Cannot escape value of invalid type "' . gettype($value));
  }

  /**
   * Escape a string to use as value in query
   *
   * @param string|int|bool|object|array $value - Value to escape. If it's object, it must implement __toString()
   * @param bool $quote
   * @return array|string
   * @throws dbException
   */
  public function escape($value, $quote = false){
    $this->lastQuery = '-- real escape string';
    if(!$this->isConnected())
      throw new dbException('Must be connected to escape string', 0, null, '-- real escape string');
    if (is_array($value)) {
      foreach($value as &$singleValue) {
        $singleValue = $this->escapeSingleValue($singleValue, true);
//        if ($quote)
//          $singleValue = '"'.$singleValue.'"';
      }
      unset($singleValue);
      return $value;
    }

    $value = $this->escapeString($value);
    if ($quote)
      $value = '"'.$value.'"';
    //$this->checkError();
    return $value;
  }


  /**
   * Alias for escape($str, true). Basically it'll include quotes around the escaped value (if needed)
   *
   * @param string|int|bool|object|array $str
   *
   * @return array|string
   * @throws dbException
   */
  public function quote($str){
    return $this->escape($str, true);
  }

  /**
   * Escapes array of strings and returns string that can be used with "IN"
   *
   * @param array $list
   * @return string
   * @throws dbException
   */
  public function escapeStringList(array $list) {
    return $this->escape($list);
  }

  /**
   * @param string $table
   * @param string|array $cond  condition fiels in format [ field=>value ] or the where clause as string
   * @param string $what
   * @return array|mixed
   */
  public function selectFirstField($table, $cond = '1', $what = '*') {
    return $this->select($table, $cond, $what, 'queryFirstField');
  }

  /**
   * @param string $table
   * @param string|array $cond  condition fiels in format [ field=>value ] or the where clause as string
   * @param string $what
   * @param int $fetchFunc
   * @param string $class
   * @return array|mixed
   */
  public function selectFirstRow($table, $cond = '1', $what = '*', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass') {
    return $this->select($table, $cond, $what, 'queryFirstRow', $fetchFunc, $class);
  }

  /**
   * @param $table
   * @param string|array $cond  condition fiels in format [ field=>value ] or the where clause as string
   * @param string $what
   * @param int $fetchFunc
   * @param string $class
   * @return array
   */
  public function selectRows($table, $cond = '1', $what = '*', $fetchFunc = self::MYSQL_ASSOC, $class = 'stdClass') {
    return $this->select($table, $cond, $what, 'queryRows', $fetchFunc, $class);
  }

  /**
   * Used in INSERT and UPDATE
   * @param array $value
   * @return string
   * @throws dbException
   */
  private function getReplaceValue(array $value) {
    $replacement = null;

    if(isset($value['replacement']))
      $replacement = $value['replacement'];

    if(is_null($replacement))
      throw new dbException('replacement not set', 0, null, '-- prepareUpdate');

    return $this->quote.$this->escape(str_replace('{value}', $replacement, $value['value'])).$this->quote;
  }

  public function prepareInsertFields($row){
    if(empty($row))
      return false;
    $fields = array_keys($row);
    $insertFields = $this->escapeField(array_shift($fields));
    if(empty($fields)){
      return '('.$insertFields.')';
    }
    foreach($fields as $field){
      $insertFields .= ','.$this->escapeField($field);
    }
    return '('.$insertFields.')';
  }

  /**
   * The argument is associative array of values with field names as keys.
   * If value is a integer or float it is passed without a change.
   * If value is array there are few cases:
   * ['type'] is set - value of type element is a function name. Supported functions are:
   * relace - replace a string. search for {value} in ['string'] and replace with ['value']
   * ['type'] is not set - use value directly, without escaping it.
   * else the value is escaped.
   *
   * Formats the values to be used in INSERT query. Complete query will be:
   * INSERT INTO <table> <result>
   *
   * @param array $fields_arr
   * @return string values part of INSERT query - SQL formated.
   * @throws dbException
   */
  public function prepareInsert(array $fields_arr){
    $fields='';
    $values='';
    reset($fields_arr);
    next($fields_arr);
    $first = true;
    foreach($fields_arr as $field=>$value){
      if($first) {
        $first = false;
      } else {
        $fields .= ',';
        $values .= ',';
      }

      $fields.= $this->escapeField($field);

      if(is_array($value) && array_key_exists('insert', $value))
        $value = $value['insert'];

      if(is_int($value) || is_float($value)){
        $values.=' '.$value;//integer types without quotes
      }else if(is_null($value)){
        $values.=' NULL';//null values are special
      }else if(is_bool($value)){
        $values .= intval($value);//boolean is converted to 0 and 1
      }else if(is_array($value)){
        if(!isset($value['type'])){// if no type element - just pass first element without change
          reset($value);
          $values.=' '.current($value);
        }else{
          switch($value['type']){
            case 'replace':
              $values.= ' '.$this->getReplaceValue($value);
              break;
          }
        }
      }else{
        /*        if(is_string($value) && !empty($value) && $value[0] == '@')
                  $values.=' '.substr($value,1);//all other types need to be escaped
                else*/
        $values.=' '.$this->quote.$this->escape($value).$this->quote;//all other types need to be escaped
      }
    }
    return '('.$values.')';
    //return '('.$fields.') values('.$values.')';
  }

  /**
   *
   * @param array $fields_arr
   * @return string
   * @throws dbException
   * @see AbstractDatabase::prepareInsert()
   */
  public function prepareUpdate(array $fields_arr){
    $result='';
    foreach($fields_arr as $field=>$value){
      if(!empty($result))$result.=',';

      if(is_array($value) && array_key_exists('update', $value))
        $value = $value['update'];

      if(is_int($value) || is_float($value)){
        $result.=' '.$this->escapeField($field).' = '.$value; // pass integer types without quotes
      }else if(is_null($value)){
        $result.=' '.$this->escapeField($field).' = NULL';
      }else if(is_bool($value)){
        // boolean is converted to 0 and 1
        $result .= ' '.$this->escapeField($field).' = '.intval($value);
      }else if(is_array($value)){
        if(!isset($value['type'])){// if no type element - just pass first element without change (or quotes!!!)
          reset($value);
          $result.=' '.$this->escapeField($field).' = '.current($value);
        }else{
          switch($value['type']){
            case 'replace':
              $result.=' '.$this->escapeField($field).' = '.$this->getReplaceValue($value);
              break;
          }
        }
      }else{
        $result.=' '.$this->escapeField($field).' = '.$this->quote.$this->escape($value).$this->quote;//all other types need to be escaped
      }
    }
    return $result;
  }

  /**
   * @param array $fields
   * @param bool $doNotJoin if this is true result will be array of conditions. if it's false the conditions will be
   * joined with "AND"
   * @return string
   * @throws dbException
   */
  public function prepareCondition(array $fields, $doNotJoin = false) {
    $conditions = [];
    foreach ($fields as $fieldName=>&$value) {
      $operator = ' = ';
      if (is_array($value)) {
        if (empty($value)) {
          $conditions[] = '0';
          continue;
        }

        $value = '('.implode(',', $this->quote($value)).')';
        $operator = ' IN ';
      }else {
        $value = $this->quote($value);
      }
      $conditions[] =  $this->escapeField($fieldName) . $operator . $value;
    }
    unset($value);
    if ($doNotJoin)
      return $conditions;
    return implode(' AND ', $conditions);
  }

  public function insertIgnore($table, array $fields_arr){
    return $this->insert($table, $fields_arr, true);
  }


}
