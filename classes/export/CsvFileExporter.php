<?php

namespace app\export;


use Exception;

class CsvFileExporter implements FileExporterr {

  function exportString() {
    $ns = ns(); $db = $ns->db;
    $userId = $ns->userId;
    $result = 'id,user_id,cat_id,title,login,url,password,description,secure_note,hash,source
';
    try {
      $rows = $db->queryRows('SELECT * FROM records WHERE user_id = ' . $db->quote($userId) . ' FOR UPDATE');

      foreach($rows as $row) {
        foreach($row as $key => $value) {
          $value = str_replace('"', '""', $value);
          if(strpos($value, ' ') || strpos($value, ',') || empty($value)) {
            $value = '"' . $value . '"';
          }
          $result .= $value;
          $result .= ',';
        }
        $result .= '
';
      }
    } catch (Exception $e) {
      $ns->errorReporter->handleException($e);
    }
    return $result;
  }

  function exportFile() {
    $myfile = fopen("testCSV.csv", "w");
    fwrite($myfile, $this->exportString());
    fclose($myfile);
    return $myfile;
  }
}
