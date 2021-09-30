<?php

namespace app\import;


use app\db\EncryptedRecord;
use app\db\Record;
use nsfw\database\dbException;

class KeeperImporter implements FileImporter {
  /**
   * @param string $filename
   */
  function importFile($filename) {
    $ns = ns(); $db = $ns->db;

//    var_dump($filename);
    $records = $this->parseFile($filename);
    $db->startTransaction();
    try {
      $db->query('DELETE FROM records WHERE source = "keeper"');
      $n=0;
      foreach($records as $record) {
        $this->importRecord($record);
        ++$n;
      }
      $db->commit();
      $ns->errorReporter->infoRedirect('/import.html', 'Imported '.$n.' records');
    } catch (\Exception $e) {
      $db->rollback();
      throw $e;
    }
    exit;
  }

  /**
   * @param array $data
   *
   * @throws dbException
   */
  protected function importRecord(array $data) {
    $ns = ns(); $db = $ns->db;

    $rec = new Record();
    $rec->userId = $ns->userId;
    $rec->title = $data['title'];
    $rec->login = $data['login'];
    $rec->password = $data['password'];
    $rec->secureNote = $data['note'];
    $erec = new EncryptedRecord();
    $erec->importRecord($rec);
    $data = $erec->getEncryptedData();
    $data['source'] = 'keeper';
    $db->insert('records', $data);
  }

  /**
   * @param $filename
   *
   * @return array|false
   */
  public function parseFile($filename) {
    static $fieldNames = [
      0 => 'category',
      1 => 'title',
      2 => 'login',
      3 => 'password',
      4 => 'url',
      5 => 'note',
      6 => 'note',
    ];

    $records = [];
    $row = 1;
    if(($handle = fopen($filename, "r")) === false)
      return false;

    while(($data = fgetcsv($handle, 1000, ",")) !== false) {
      $record = [];
      $extraFields = [];
      $num = count($data);
//        echo "<p> $num fields in line $row: <br /></p>\n";
      $row++;
      for($c = 0; $c < $num; $c++) {
        if($c <= 6) {
          $fieldName = $fieldNames[$c];
          $record[$fieldName] = $data[$c];
        } else {
          $fieldName = $data[$c];
          ++$c;
          $extraFields[$fieldName] = $data[$c];
        }
//          echo 'field '.$c .': '. $data[$c] . "<br />\n";
      }
      $record['extra'] = $extraFields;

      $records[] = $record;
//        var_dump($record);echo '<br />';
    }
    fclose($handle);
//    var_dump($records);

    return $records;
  }

}
