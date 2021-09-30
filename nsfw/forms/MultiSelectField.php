<?php
/**
 * User: npelov
 * Date: 13-06-17
 * Time: 5:35 PM
 */

namespace nsfw\forms;


use nsfw\errors\iErrorReporter;
use nsfw\template\CascadedTemplate;

class MultiSelectField extends AbstractFormField {
  public static $defaultTplFile = 'multi_select.html';
  protected $showSelectAll = true;
  protected $options = [];
  protected $groups = [];
  protected $tpl;
  protected $lineHeight = 20;
  public $size = 6;
  /** @var string */
  protected $tplFile;

  public function __construct($name, $value = null, iErrorReporter $errorReporter = null) {
    parent::__construct($name, $value, $errorReporter);
    $this->tplFile = self::$defaultTplFile;
  }

  /**
   * @return string
   */
  public function getTplFile() {
    return $this->tplFile;
  }

  /**
   * @param string $tplFile
   */
  public function setTplFile($tplFile) {
    $this->tplFile = $tplFile;
  }

  public function getType() {
    return 'multiselect';
  }

  public function getFromPost($gpc = 'PG'){
    $values = getParam($this->name, array(), strToUpper($gpc));
    $this->setChecked(array_keys($values));
  }

  public function addOption($value, $text, $checked = false){
    $option = new MultiSelectOption();
    $option->value = $value;
    $option->text = $text;
    $option->checked = $checked;
    $this->options[$value] = $option;
  }

  public function setOptions(array $items){
    $this->clearOptions();
    foreach($items as $value=>$text){
      $this->addOption($value, $text);
    }
  }

  public function clearOptions() {
    $this->options = array();
  }

  function getChecked(){
    $checked = array();
    foreach($this->options as $option){
      if($option->checked)
        $checked[$option->value]= $option->text;
    }
    return $checked;
  }

  function setChecked(array $values){
    $values = array_flip($values);
    foreach($this->options as $option){
      $option->checked = array_key_exists($option->value, $values);
    }
  }

  function setName($name){
    parent::setName($name);
    $this->addClass($this->name);
  }

  protected function getTemplate() {
    $this->tpl = null; // we don't have reset method. Just recreate - not a big deal (when we implement cache).
    if(empty($this->tpl)) {
      $this->tpl = new CascadedTemplate();
      $this->tpl->loadFromFile($this->tplFile);
    }
    return $this->tpl;
  }

  function getHtml() {
    $tpl = $this->getTemplate();
    $tpl->attributes = $this->getAttributesHtml();
    $tpl->itemsboxAttributes = 'style="height:'.($this->size*$this->lineHeight).'px"';
    $tpl->fieldName = $this->name;
    foreach($this->options as $value=>$item){
      $tpl->getBlock('options')->appendRow([
        'value' => $item->value,
        'text' => $item->text,
        'classSelected' => $item->checked?' rowSelected':'',
        'checked' => $item->checked?' checked="checked"':'',
      ]);
    }
    $tpl->selectAll->visible = $this->showSelectAll;
    return $tpl->getParsed();
  }


}
