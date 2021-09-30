<?php

namespace action;

use app\db\EncryptedRecord;
use app\db\Record;
use app\forms\RecordForm;
use nsfw\controller\AbstractAction;

require_once __DIR__.'/newrecord.php';

class edit_record extends newrecord {
  protected function init() {
    $ns = ns(); $db = $ns->db;

    $form =  parent::init();
    $recordId = getParam('id', 0, 'PG');
    if(empty($recordId))
      $ns->errorReporter->errorRedirect('/', 'Record '.$recordId.' does not exist');
    $record = Record::createById($db, $recordId);
    $er = new EncryptedRecord();
    $er->importEncryptedRecord($record);

    $form->setData($er->getData());
    return $form;
  }


}
