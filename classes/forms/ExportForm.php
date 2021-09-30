<?php


namespace app\forms;

use nsfw\forms\TemplateForm;
use nsfw\forms\WebForm;


class ExportForm extends TemplateForm {
  public function __construct() {
    parent::__construct();
    $this->errorReporter = ns()->errorReporter;
    $this->loadFromXmlFile(PROJECT_DIR . '/data/forms/export.xml');
  }
}
