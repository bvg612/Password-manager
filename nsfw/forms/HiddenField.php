<?php

namespace nsfw\forms;


class HiddenField extends AbstractFormField{
  public function getType() {
    return 'hidden';
  }

  function getHtml() {
    $html = '<input type="hidden" name="'.$this->getName().'" value="'.$this->getValue().'"'.$this->getAttributesHtml().' />';
    return $html;
  }

}
