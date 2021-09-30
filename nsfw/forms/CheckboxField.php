<?php

namespace nsfw\forms;


use nsfw\errors\ErrorReporter;

class CheckboxField extends AbstractFormField{
  /** @var bool */
  protected $checked = false;
  /** @var bool|mixed */
  protected $uncheckedValue = false;

  /**
   * CheckboxField constructor.
   */
  public function __construct($name, $value = null, ErrorReporter $errorReporter = null) {
    $this->setValue(1);
    parent::__construct($name, $value, $errorReporter);
    $this->default = false;
  }


  /**
   * @return boolean
   */
  public function isChecked() {
    return $this->checked;
  }

  /**
   * @param boolean $checked
   */
  public function setChecked($checked) {
    $this->checked = $checked;
  }

  /**
   * @return bool|mixed
   */
  public function getUncheckedValue() {
    return $this->uncheckedValue;
  }

  /**
   * @param bool|mixed $uncheckedValue
   */
  public function setUncheckedValue($uncheckedValue) {
    $this->uncheckedValue = $uncheckedValue;
  }

  function getHtml() {

    $checked = $this->checked?' checked="checked"':'';
    $html = '<input type="checkbox" name="'.$this->getName().'" value="'.htmlspecialchars($this->getValue()).'" '.$this->getAttributesHtml().' '.$checked.' />';
    return $html;
  }

  public function getType() {
    return 'checkbox';
  }

  public function getFromPost() {
    $this->checked = getParam($this->name, false, $this->paramOrder) !== false;
  }


}
