<?php
/**
 * User: npelov
 * Date: 12-05-17
 * Time: 3:41 PM
 */

namespace nsfw\users;


use Exception;
use nsfw\database\Database;

/**
 * Class AbstractFieldObject
 * @package nsfw\users
 *
 * @property int $id
 *
 */
abstract class AbstractFieldObject {
  /** @var Database */
  protected $db;
  protected $fields = ['id' => 0];
  protected $obj2db = [];
  protected $db2obj = [];
  /** @var AbstractAccount */
  protected $account;

  /**
   * AbstractUser constructor.
   * @param Database $db
   * @throws Exception
   */
  public function __construct(Database $db) {
    $this->db = $db;
    // do not allow setting of ID
    if(isset($fields['id'])) {
      throw new Exception('The inheritor is now allowed to set the id');
      //unset($fields['id']);
    }
    $this->addFields($this->getFields());
  }

  protected function addFields(array $fields) {
    $this->fields = array_merge($this->fields, array_fill_keys(array_keys($fields), null));
    foreach($fields as $field=>$dbField) {
      $this->fields[$field] = null;
      if($field != $dbField) {
        $this->obj2db[$field] = $dbField;
        $this->db2obj[$dbField] = $field;
      }
    }
  }


  /**
   * Parent must implement this field and return array of fields in format [ property => dbField ]
   * @return array
   */
  abstract protected function getFields();

  /**
   * @return bool
   */
  public function isRegistered() {
    return !empty($this->fields['id']);
  }

  /**
   * @param array $fields
   */
  protected function import(array $fields) {
    foreach($this->fields as $fieldName) {
      if(array_key_exists($fieldName,$fields))
        $this->fields[$fieldName] = $fields[$fieldName];
    }
  }

  /**
   * @param string $field Property name (in both conversion functions)
   * @param mixed $objValue object value
   * @return string database value
   */
  protected function convertValue2Db($field, $objValue) {
    $convertCb = [$this, 'convert'.$field.'2db'];
    if(is_callable($convertCb))
      return call_user_func($convertCb, $objValue);
    return $objValue;
  }

  /**
   * Uses callbacks to convert database value to object value. Example: If you have isAdmin which is integer in db, but
   * you want boolean in object you define these two methods:
   *
   * bool convertIsAdmin2obj($dbValue);
   * string convertIsAdmin2db($objValue); // db value should be string, but if it's integer it'll be converted
   *
   * @param string $field Property name (in both conversion functions)
   * @param string $dbValue database value
   * @return mixed object value
   */
  protected function convertValue2Obj($field, $dbValue) {
    $convertCb = [$this, 'convert'.$field.'2obj'];
    if(is_callable($convertCb))
      return call_user_func($convertCb, $dbValue);
    return $dbValue;
  }

  /**
   * @param array $dbFields
   */
  protected function importDb(array $dbFields) {
    foreach($this->fields as $fieldName=>$fieldValue) {
      $dbFieldName = $fieldName;
      if(!empty($this->obj2db[$fieldName]))
        $dbFieldName = $this->obj2db[$fieldName];
      if(array_key_exists($dbFieldName, $dbFields))
        $this->fields[$fieldName] = $this->convertValue2Obj($fieldName, $dbFields[$dbFieldName]);
    }
  }

  protected function exportToDb() {
    $dbFields = [];
    foreach($this->fields as $fieldName=>$fieldValue) {
      $dbFieldName = $fieldName;
      if(array_key_exists($fieldName, $this->obj2db))
        $dbFieldName = $this->obj2db[$fieldName];
      $dbFields[$dbFieldName] = $this->convertValue2Db($fieldName, $fieldValue);
    }
    return $dbFields;
  }

  function __isset($name) {
    if(array_key_exists($name, $this->fields))
      return true;
    $method = 'get'.$name;
    if(method_exists($this, $method))
      return true;
    return false;
  }

  public function __get($name) {
    if(array_key_exists($name, $this->fields))
      return $this->fields[$name];
    $method = 'get'.$name;
    if(method_exists($this, $method))
      return call_user_func([$this, $method]);
    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
    return null;
  }

  function __set($name, $value) {
    if(array_key_exists($name, $this->fields)) {
      $this->fields[$name] = $value;
      return;
    }
    //var_dump($name, $this->fields);

    $method = 'set'.$name;
    if(method_exists($this, $method)) {
      call_user_func([$this, $method], $value);
      return;
    }

    $trace = debug_backtrace();
    $file = 'unknown';
    if(!empty($trace[0]['file']))
      $file = $trace[0]['file'];
    $line = 0;
    if(!empty($trace[0]['line']))
      $line = $trace[0]['line'];
    trigger_error(
      'Undefined magic property via '.get_class($this).'->__set(): ' . $name .
      ' in ' . $file .
      ' on line ' . $line,
      E_USER_WARNING);
  }

}

