<?php


namespace app\forms;

use nsfw\forms\TemplateForm;
use nsfw\i18\Language;

class RecordForm extends TemplateForm {
  public function __construct() {
    parent::__construct();
    $this->errorReporter = ns()->errorReporter;
    $this->loadFromXmlFile(PROJECT_DIR.'/data/forms/record.xml');
    $this->tpl->setVar('id', getParam('id'));

  }


}
