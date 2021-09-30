<?php

namespace nsfw\forms;


class DisplayField extends AbstractFormField{
  public function getType() {
    return 'display';
  }

  function getHtml() {
    $html = '<input type="hidden" name="'.$this->getName().'" value="'.$this->getValue().'" />';
    $html .= htmlspecialchars($this->getValue());
    return $html;
  }

}
