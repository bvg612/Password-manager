<?php

namespace action;

use app\forms\ImportForm;
use app\import\KeeperImporter;
use nsfw\controller\AbstractAction;
use nsfw\forms\FileField;


class import extends AbstractAction {


  function runEnd() {
//    $tpl = $this->createCenterTemplate('import.html');
    $form = new ImportForm();
    $tpl = $this->setCenterForm($form);
    if(!$form->processPost())
      return;

    switch($form->type){
      case 'keeper':
        $importer = new KeeperImporter();
        /** @var FileField $ff */
        $ff = $form->getField('file');
        $importer->importFile($ff->value['tmp_name']);
        break;
    }

  }

}
