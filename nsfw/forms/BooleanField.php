<?php

namespace nsfw\forms;


use nsfw\errors\ErrorReporter;

class BooleanField extends AbstractFormField{
  /** @var bool */
  protected $checked = false;

  /**
   * CheckboxField constructor.
   */
  public function __construct($name, $value = null, ErrorReporter $errorReporter = null) {
    $this->setValue(1);
    parent::__construct($name, $value, $errorReporter);
    $this->default = false;
  }

  function getHtml() {

    $checked = $this->getValue()?' checked="checked"':'';
    $html = '<input type="checkbox" name="'.$this->getName().'" value="1" '.$this->getAttributesHtml().' '.$checked.' />';
    return $html;
  }



  public function getType() {
    return 'boolean';
  }

  public function getFromPost() {
    $this->value = getParam($this->name, false, $this->paramOrder) !== false;
  }


}
