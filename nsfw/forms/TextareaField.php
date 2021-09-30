<?php

namespace nsfw\forms;


class TextareaField extends AbstractFormField{
  public function getType() {
    return 'textarea';
  }

  function getHtml() {
    $html = '<textarea name="'.$this->getName().'"'.$this->getAttributesHtml().'>';
    $html .= htmlspecialchars($this->getValue());
    $html .= '</textarea>';
    return $html;
  }

}
