<?php

namespace nsfw\forms;


class PasswordField extends AbstractFormField{

  public function getType() {
    return 'password';
  }

  function getHtml() {
    $html = '<input type="password" name="'.htmlspecialchars($this->getName()).'" value="'.htmlspecialchars($this->getValue()).'"'.$this->getAttributesHtml().' />';
    return $html;
  }


}
