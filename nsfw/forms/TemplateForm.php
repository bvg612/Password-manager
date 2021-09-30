<?php
/**
 * User: npelov
 * Date: 12-06-17
 * Time: 4:39 PM
 */

namespace nsfw\forms;

/**
 * Class TemplateForm
 *
 * Manually place each input in the template with variables like {%fl_<name>} for label and {%f_<name>} for the input
 * {%f_hiddenFields} - hidden fields
 * {%f_formTag} - form open tag
 * {%f_closeForm} - form close tag
 *
 * @package nsfw\forms
 */
class TemplateForm extends WebForm {

  private function setTemplateFields() {
    $tpl = $this->tpl;
    foreach($this->fields as $name => $field) {
      /** @var FormField $field */
      if($field instanceof HiddenField)
        continue;
      $name = $field->getName();
      $tpl->setVar('fl_' . $name, $field->getLabelHtml());
      $tpl->setVar('f_' . $name, $field->getHtml());

    }
  }

  function getHtml() {
    $tpl = $this->tpl;
    $tpl->setVar('f_closeForm', '</form>');
    $tpl->setVar('f_formTag', $this->getFormTag());
    $this->setTemplateFields();
    $this->setHiddenFields($tpl);
    return $this->tpl->getParsed();
  }
}
