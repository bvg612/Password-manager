<?php
/**
 * User: npelov
 * Date: 18-05-17
 * Time: 3:41 PM
 */

namespace nsfw\forms;

class SelectField extends AbstractFormField{
  protected $options = [];

  public function getType() {
    return 'select';
  }

  public function getHtml() {
    $fieldValue = $this->getValue();
    $html = '<select name="'.htmlspecialchars($this->getName()).'" '.$this->getAttributesHtml().' >'; // wtf:  value="'.$this->getValue().'"
    foreach($this->options as $optValue=>$text) {
      $select = '';
      if($optValue == $fieldValue)
        $select = ' selected="selected"';
      $html .= '<option value="'.htmlspecialchars($optValue).'" '.$select.'>'.htmlspecialchars($text).'</option>';
    }
    $html .= '</select>';
    return $html;
  }

  /**
   * @param int|string $value
   * @param string $text
   */
  public function addOption($value, $text) {
    $this->options[$value] = $text;
  }

  public function addOptionsAssoc(array $options) {
    foreach($options as $value=>$text) {
      $this->addOption($value, $text);
    }
  }

}
