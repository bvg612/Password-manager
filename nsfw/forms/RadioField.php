<?php

namespace nsfw\forms;


use nsfw\errors\iErrorReporter;
use nsfw\template\SimpleTemplate;
use nsfw\template\Template;

class RadioField extends AbstractFormField{
  protected static $template = '<label><input type="radio" name="{%q_name}" value="{%q_value}" title="{%q_title}" {%attr} />{%q_label}</label><br />';
  /** @var Template */
  protected $tpl;
  protected $options = [];

  public function __construct($name, $value = null, iErrorReporter $errorReporter = null) {
    parent::__construct($name, $value, $errorReporter);
    $this->initTemplate();
  }

  protected function initTemplate() {
    $this->tpl = new SimpleTemplate(static::$template);
  }

  public function addOption($value, $label) {
    $this->options[$value] = $label;
  }

  public function getOptions() {
    return $this->options;
  }

  public function setTemplate($template) {
    $this->tpl->setTemplate($template);
  }

  function getHtml() {
    $tpl = $this->tpl;
    $tpl->reset();
    foreach($this->options as $value=>$label) {
      $attributes = [];
      $tpl->setVars([
        'name' => $this->name,
        'value' => $value,
        'label' => $label,
      ]);
    }
    return $tpl->getParsed();
  }

  public function getType() {
    return 'radio';
  }

}
