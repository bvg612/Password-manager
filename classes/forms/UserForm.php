<?php


namespace app\forms;

use nsfw\forms\TemplateForm;
use nsfw\i18\Language;

class UserForm extends TemplateForm {
  public function __construct() {
    parent::__construct();
    $this->errorReporter = ns()->errorReporter;
    $this->loadFromXmlFile(PROJECT_DIR.'/data/forms/user.xml');
    $this->tpl->setVar('id', getParam('id')); //do i need this?
  }


}
