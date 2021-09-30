<?php

namespace nsfw\forms;


class TextField extends AbstractFormField{
  function getHtml() {
    $html = '<input type="text" name="'.$this->getName().'" value="'.htmlspecialchars($this->getValue()).'"'.$this->getAttributesHtml().' />';
    return $html;
  }

  public function getType() {
    return 'text';
  }

}
