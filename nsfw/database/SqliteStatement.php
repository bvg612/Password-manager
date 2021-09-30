<?php
/**
 * User: npelov
 * Date: 11-11-17
 * Time: 1:43 PM
 */

namespace nsfw\database;


use SQLite3Result;
use SQLite3Stmt;

class SqliteStatement implements Statement {
  /** @var SQLite3Stmt */
  protected $stmt;
  /** @var SQLite3Result */
  protected $result;
  /** @var \SQLite3 */
  protected $sqlite;
  protected $sql;
  protected $fields;

  /**
   * SqliteStatement constructor.
   * @param string $sql
   * @param array $fields
   * @throws dbException
   */
  public function __construct(SqliteDatabase $sqliteDb, $sql, array $fields = []) {
    $this->sql = $sql;
    $db = $this->sqlite = $sqliteDb->getLink();
    $stmt = @$db->prepare($sql);
    if(empty($stmt)){
      throw new dbException($db->lastErrorMsg(), $db->lastErrorCode(), null, $sql);
    }
    $this->stmt = $stmt;
    if(!empty($fields)) {
      foreach($fields as $fieldName => $value) {
        $this->bindValue(':' . $fieldName, $value);
      }
    }
  }

  /**
   * @return SQLite3Result
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * @param $fieldName
   * @param $value
   * @return array
   * @throws DbException
   */
  protected function getBindInfo($fieldName, $value){
    $bindValue = $value;
    $bindType = SQLITE3_TEXT;
    if(is_string($value)){
      if(!empty($value)){ // empty string is TEXT
        if(preg_match('/^[-+]?[0-9]+$/', $value))
          $bindType = SQLITE3_INTEGER;
        else if(preg_match('/^[-+]?[0-9]+\\.?[0-9]*$/', $value))
          $bindType = SQLITE3_FLOAT;
      }
    }else if(is_array($value)){
      if(array_key_exists('value', $value)){
        $bindValue = $value['value'];
        if(!empty($value['type']))
          $bindType = $value['type'];
      }else{
        throw new dbException('Value of field "'.$fieldName.'" is array, but array key "value" is not present', 0, null, '--getBindInfo()');
      }
    }else if(is_null($value)){
      $bindType = SQLITE3_NULL;
    }else{
      switch (gettype($value)){
        case 'double': $bindType = SQLITE3_FLOAT; break;
        case 'integer': $bindType = SQLITE3_INTEGER; break;
        case 'boolean': $bindType = SQLITE3_INTEGER; break;
      }
    }

    return [
      'value' => $bindValue,
      'type' => $bindType,
    ];
  }

  /**
   * @param $fieldName
   * @param $value
   * @throws DbException
   */
  protected function bindValue($fieldName, $value){
    $bindInfo = $this->getBindInfo($fieldName, $value);
    $this->fields[$fieldName] = $value;
    $this->stmt->bindValue($fieldName, $bindInfo['value'], $bindInfo['type']);
  }

  /**
   * @param array $fields
   * @throws DbException
   */
  public function bind(array $fields) {
    foreach($fields as $fieldName=>$value){
      $this->bindValue(':'.$fieldName, $value);
    }

  }

  /**
   * @param array $fields
   * @return SQLite3Result
   * @throws dbException
   */
  public function execute($fields = []) {
    $db = $this->sqlite;
    if(!empty($fields))
      $this->bind($fields);
    $this->result = @$this->stmt->execute();
    if(!$this->result){
      throw new dbException($db->lastErrorMsg(), $db->lastErrorCode(), null, $this->sql."\nprepared statement fields: ".print_r($this->fields, true));
    }
    return $this->result;
  }

  /**
   * @return array
   * @throws dbException
   */
  public function getFieldIndexes() {
    $result = $this->result;
    if(empty($result)) {
      throw new dbException('Result is empty', 0, null, $this->sql."\nprepared statement fields: ".print_r($this->fields, true));
    }
    $numCols = $result->numColumns();

    $fields = [];
    $fieldIndexes = [];
    for($i = 0; $i < $numCols; ++$i){
      $name = $result->columnName($i);
      //$type = $result->columnType($i);
      $fields[$i] = $name;
      $fieldIndexes[$name] = $i;
    }

    return array($fields, $fieldIndexes);
  }

  public function close() {
    if(empty($this->stmt))
      return;
    $this->stmt->close();
    $this->stmt = null;
  }

  public function __destruct() {
    $this->close();
  }


}
