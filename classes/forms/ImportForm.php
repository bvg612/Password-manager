<?php


namespace app\forms;

use nsfw\forms\SelectField;
use nsfw\forms\TemplateForm;
use nsfw\forms\WebForm;
use nsfw\i18\Language;

class ImportForm extends WebForm {
  public function __construct() {
    parent::__construct();
    $this->errorReporter = ns()->errorReporter;
    $this->loadFromXmlFile(PROJECT_DIR.'/data/forms/import.xml');
    $this->tpl->setVar('submitTitle', 'Import');
    /** @var SelectField $ff */
    $ff = $this->getField('type');
    $ff->addOptionsAssoc([
      'keeper' => 'KeeperSecurity',
    ]);
    $ff->value = 'keeper';
  }


}
