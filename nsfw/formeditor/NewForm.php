<?php

namespace nsfw\formeditor;


use nsfw\forms\AbstractForm;

/**
 * Class NewForm
 *
 * Form class for creating new forms (and editing old ones?).
 *
 * @package nsfw\formeditor
 *
 * @ToDo: delete this file - it's probably not used
 */
class NewForm extends FormEditorForm{
  public function __construct() {
    parent::__construct();
    $this->loadFromFile('new-form.xml');
  }

}
