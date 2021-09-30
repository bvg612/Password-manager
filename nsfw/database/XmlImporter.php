<?php
/**
 * User: npelov
 * Date: 02-07-17
 * Time: 4:39 PM
 */

namespace nsfw\database;


use LibXMLError;
use SimpleXMLElement;

class XmlImporter {
  /** @var Database */
  protected $db;

  protected $doc;
  protected $tableName = '';

  /**
   * LanguageData constructor.
   * @param Database $db
   */
  public function __construct(Database $db) {
    $this->db = $db;
  }

  /**
   * @return string
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * @param $xmlFile
   * @return bool
   * @throws \Exception
   */
  public function openFile($xmlFile) {
    if(!empty($this->doc))
      $this->close();
    try {
      $this->doc = new \SimpleXMLElement($xmlFile, 0, true);
      $this->tableName = $this->doc->getName();
    }catch (\Exception $e) {
      $errors = libxml_get_errors();
      /** @var LibXMLError $lastError */
      $lastError = end($errors);
      throw new \Exception('xml error on line '.$lastError->line.': '.$lastError->message, count($errors), $e);
    }
    if(empty($this->doc))
      return false;
    return true;
  }

  public function close() {
    $this->doc = null;
  }

  /**
   * Runs the query in <structure> element. Make sure it has "IF NOT EXISTS"
   */
  public function importStructure() {
    $structure = trim(strval($this->doc->structure));
    if(empty($structure))
      throw new \Exception('Create query is empty. Please put create query into <structure> element');

    $tables = $this->db->queryRows('SHOW TABLES LIKE  "'.$this->db->escape($this->tableName).'"');
    if(count($tables) > 0)
      return;
    $this->db->query(strval($structure));
  }

  /**
   * @param bool $onlyIfEmpty if this is true data will be imported only if the destination table is empty
   */
  public function importData($onlyIfEmpty = false) {
    $db = $this->db;
    if($onlyIfEmpty) {
      $hasRecords = $db->queryFirstField('SELECT count(*) FROM '.$db->escapeField($this->tableName).' LIMIT 1');
        if($hasRecords)
          return;
    }
    $data = $this->doc->data;
    $dbRows = [];
    foreach($data->row as $row) {
      /** @var SimpleXMLElement $row */
      $fields = [];
      foreach($row->children() as $field) {
        /** @var $field SimpleXMLElement */
        $fields[$field->getName()] = strval($field);
      }
      $db->insertIgnore($this->tableName, $fields);
    }

  }

  /**
   * @param string $file
   */
  public function import($file) {
    $this->openFile($file);
    $this->importStructure();
    $this->importData(true);
    $this->close();
  }
}
