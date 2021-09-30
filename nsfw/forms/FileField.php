<?php

namespace nsfw\forms;


use nsfw\errors\iErrorReporter;

class FileField extends AbstractFormField{

  /**
   * FileField constructor.
   */
  public function __construct($name, $value = null, iErrorReporter $errorReporter = null) {
    parent::__construct($name, $value, $errorReporter);
    $this->default = [
      'name' => '',
      'type' => '',
      'tmp_name' => '',
      'error' => UPLOAD_ERR_NO_FILE,
      'size' => 0,
    ];

  }

  function getHtml() {
    $html = '<input type="file" name="'.$this->getName().'" value="'.htmlspecialchars($this->getValue()).'"'.$this->getAttributesHtml().' />';
    return $html;
  }

  public function getType() {
    return 'file';
  }

  public function isEmpty() {
    /** @var array $value */
    $value = $this->value;
    return $value['error'] != UPLOAD_ERR_OK && !is_uploaded_file($value['tmp_name']) ;
  }

  public function getFromPost() {
    $this->value = $value = getParam($this->name, $this->default, 'F');
  }


}
