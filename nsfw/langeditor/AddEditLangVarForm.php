<?php
/**
 * User: npelov
 * Date: 10-07-17
 * Time: 1:03 PM
 */

namespace nsfw\langeditor;


use nsfw\forms\TemplateForm;
use nsfw\forms\TextareaField;
use nsfw\forms\TextField;
use nsfw\forms\WebForm;
use nsfw\validators\SimpleValidator;

/**
 * Class AddEditLangVarForm
 * @package nsfw\langeditor
 *
 */
class AddEditLangVarForm extends TemplateForm {

  /**
   * AddEditLangVarForm constructor.
   */
  public function __construct($action, $defaultBackUrl = './') {
    parent::__construct();
    $this->setAttribute('action', $action);
    $ff = $this->addNewField('id', 'hidden', '');

    $ff = $this->addNewField('bu', 'hidden', getParam('bu', $defaultBackUrl,'GP'));
    $ff = $this->addNewField('orgVarName', 'hidden', '', 'required');

    $ff = $this->addNewField('varName', 'text', '', 'required');
    $ff->setAttribute('class', 'ti');

    $ff = $this->addNewField('description', 'textarea', '');

  }
}
