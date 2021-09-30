<?php

namespace action;

use app\export\CsvFileExporter;
use app\forms\ExportForm;
use nsfw\controller\AbstractAction;

class export extends AbstractAction {

  function runEnd() {
    $form = new ExportForm();
    $tpl = $this->setCenterForm($form);
    //var_dump($form.type);
    if(!$form->processPost())
      return;


    $this->processPost();

  }

  private function processPost() {
   // var_dump($_SERVER['REQUEST_METHOD']);
    if($_SERVER['REQUEST_METHOD'] != 'POST')
      return;

    $exporter = new CsvFileExporter();
   // $file = $exporter->exportFile();
    $this->downloadFile($exporter->exportString(),'csv');

  }

  protected function downloadFile($contents, $extension) {
    $size = strlen($contents);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=passwords.'.$extension);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $size);
    echo $contents;
    exit;

  }


}

