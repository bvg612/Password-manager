<?php
/**
 * User: npelov
 * Date: 20-07-17
 * Time: 1:57 PM
 */

namespace nsfw\database;

use Serializable;

/**
 * Class DbRow
 *
 * This class is intended for describing database rows without any functionality - pure data class. The exception is
 * import and export methods. The class serializes without private and protected properties. All private/protected
 * should be initialized in constructor or wakeup
 *
 * <p>
 * Make sure you set static property $table if you use createByField.
 *
 * You might want to add <i>@method</i> for createByField methods to fix the return type
 *
 * Names that are different in class public fields and database are converted automatically if database is
 *  <b>underscore_style</b> and the class properties is <b>camelStyle</b>. Otherwise you need to call
 * DbRow::setConvert(array) in constructor.
 *
 * @package nsfw\database
 */
abstract class DbRow implements Serializable,\Countable {
  const CONVERT_TO_PROPERTY = 1;
  const CONVERT_TO_DB = 2;
  /** @var array Names in format [ dbName => propertyName ] */
  private $convert = [];
  private $exportConvert = [];
  protected static $dbTable;
  /** @var string Example: SELECT * FROM table WHERE {%field} = "{%value}" */
  protected static $selectSql = /** @lang text */ 'SELECT * FROM {%table} WHERE {%field} = "{%value}"';
  /** @var callable converts properties names to db names and the other way around  */
  protected static $convertFunction = [__CLASS__, 'convertFunction'];
  /**
   * @var array Sets types for fields. when fields are typed type conversion will be done on load from/store to db
   * Use php name, not db name
   * Available types:
   * bool db is integer, php is bool
   * int
   */
  protected $types = [];

  public static function getTable() {
    return static::$dbTable;
  }

  /**
   * @param string $sql
   */
  public static function setSelectSql($sql) {
    static::$selectSql = $sql;
  }

  /**
   * Creates the DbRow object from an array or object returned from database. It's <b>not</b> required the input to have
   * all the fields.
   *
   * @param object|array $row
   * @return DbRow
   */
  public static function createFromDbRow($row) {
    if(empty($row))
      return null;

    if(!is_array($row) && !is_object($row)) {
      throw new \InvalidArgumentException('createFromDbRow(): First parameter must be array or object');
    }
    $obj = new static();
    $obj->importDb($row);
    return $obj;
  }

  /**
   * @return string
   */
  public function serialize () {
    return serialize(\nsfw\getPublicMembers($this));
  }

  /**
   * @param array $values Names in format [ dbName => propertyName ]
   */
  protected function setConvert(array $values) {
    $this->convert = $values;
    if(!empty($this->exportConvert))
      $this->exportConvert = [];
  }

  /**
   * @param string $serialized
   */
  public function unserialize($serialized) {
    $this->import(unserialize($serialized));
  }

  public function getDbName($name) {
    if(array_key_exists($name, $this->exportConvert)) {
      return $this->exportConvert[$name];
    }

    if(is_callable(self::$convertFunction))
      return call_user_func(self::$convertFunction, $name, self::CONVERT_TO_DB);

    return $name;
  }

  public function getPropertyName($name) {
    if(array_key_exists($name, $this->convert)) {
      return $this->convert[$name];
    }

    if(is_callable(self::$convertFunction))
      return call_user_func(self::$convertFunction, $name, self::CONVERT_TO_PROPERTY);

    return $name;
  }

  /**
   * Imports data using class member names. It's not required the input to have <b>all</b> the fields. Only existing ones will
   * be set. The input can have extra fields - they will be ignored
   *
   * @param object|array $row
   * @param array $translate
   */
  public function import($row, $translate = []) {
    $public = \nsfw\getPublicMembers($this);
    foreach($row as $name => $value) {
      if (array_key_exists($name, $translate))
        $name = $translate[$name];

      if(!array_key_exists($name, $public))
        continue;
      $this->$name = $value;
    }
  }

  /**
   * Imports data using database field names.
   * @param object|array $row This can also be an object - only public members will be processed in this case.
   * @param array $translate
   */
  public function importDb($row, $translate = []) {
    $public = \nsfw\getPublicMembers($this);
    foreach($row as $dbName=>$value) {
      $name = $this->getPropertyName($dbName);
      if (array_key_exists($name, $translate))
        $name = $translate[$name];

      if(!array_key_exists($name, $public))
        continue;

      $processValueMethod = 'getPhpValue'.$name;
      if(is_callable([$this, $processValueMethod])) {
        $value = call_user_func([$this, $processValueMethod], $value);
      }else {
        if(array_key_exists($name, $this->types)) {
          switch($this->types[$name]) {
            case 'bool':
            case 'boolean':
              $value = boolval($value);
              break;
            case 'int':
            case 'integer':
              $value = intval($value);
              break;
          }
        }
      }

      $this->$name = $value;
    }
  }

  /**
   * Exports an array containing the data in the same way that Database::queryFirstRow does.
   * @param string[] $translate name map
   * @return array
   */
  public function exportDb($translate = []) {
    if(!empty($this->convert) && empty($this->exportConvert)) {
      $this->exportConvert = array_flip($this->convert);
    }
    $row = [];
    $public = \nsfw\getPublicMembers($this);
    foreach($public as $name => $value) {
      if (array_key_exists($name, $translate))
        $name = $translate[$name];
      $dbName = $this->getDbName($name);

      $processValueMethod = 'getDbValue'.$name;
      if(is_callable([$this, $processValueMethod]))
        $value = call_user_func([$this, $processValueMethod], $value);

      $row[$dbName] = $value;
    }
    return $row;
  }

  /**
   * Exports data using member names
   * @param string[] $translate
   * @return array
   */
  public function export($translate = []) {
    $row = [];
    $public = \nsfw\getPublicMembers($this);
    foreach($public as $name => $value) {
      if (array_key_exists($name, $translate))
        $name = $translate[$name];
      $row[$name] = $value;
    }
    return $row;
  }

  public function count() {
    $fields = \nsfw\getPublicMembers($this);
    return count($fields);
  }

  /**
   * @param Database $db
   * @param string $field
   * @param string|int|null $value
   * @return DbRow
   * @throws dbException
   */
  public static function createByField(Database $db, $field, $value) {
    if(empty(static::$dbTable))
      throw new \RuntimeException('Set '.get_called_class().'::dbTable before using this class');
    $sql = str_replace(
      ['{%table}','{%field}', '{%value}'],
      [static::$dbTable, $field, $db->escape($value)],
      static::$selectSql
    );

    $row = $db->queryFirstRow($sql);
    if(empty($row))
      return null;
    $obj = new static();
    $obj->importDb($row);
    return $obj;
  }

  /**
   * @param Database $db
   * @param int $id
   * @return DbRow
   * @throws dbException
   */
  public static function createById(Database $db, $id) {
    if(empty($id))
      return null;
    return static::createByField($db, 'id', $id);
  }

  public static function convertFunction($field, $direction = self::CONVERT_TO_PROPERTY) {
    if($direction == self::CONVERT_TO_PROPERTY) {
      $pieces = explode('_', $field);
      if(count($pieces) == 1)
        return $field;
      array_walk($pieces, function(&$value, $key){ if($key == 0) return $value; return $value = ucfirst($value);});
      return implode('', $pieces);
    } else if($direction == self::CONVERT_TO_DB) {
      $pieces = preg_split('/(?=[A-Z])/',$field);
      if(count($pieces) == 1)
        return $field;
      array_walk($pieces, function(&$value, $key){ return $value = strtolower($value);});
      return implode('_', $pieces);
    }
    throw new \InvalidArgumentException('bad direction '.$direction.' in second parameter');
  }


}
