<?php


namespace nsfw\template;

use nsfw\cache\Cache;


/**
 * Class SimpleTemplate
 * @package nsfw\template
 *
 * @method static SimpleTemplate createFromFile($filename)
 */
class SimpleTemplate extends AbstractTemplate {
  use TemplateTraits;

  protected $template;
  protected $vars = [];

  /**
   * SimpleTemplate constructor.
   * @param string $tpl
   * @param Cache|null $cache
   */
  public function __construct($tpl= '', Cache $cache = null) {
    parent::__construct($tpl, $cache);
    if(empty($this->config)) {
      $this->config = static::getDefaultConfig();
    }

    $this->filePathProcessor = new FilePathProcessor($this->config);
    $this->preProcessors[] = $this->filePathProcessor;

  }

  function loadFromFile($filename) {
    $this->setTemplate(file_get_contents($this->filePathProcessor->getFilePath($filename)));
  }

  function setTemplate($template) {
    $this->template = $template;
  }

  function getTemplate() {
    return $this->template;
  }

  function replaceVars($tpl) {
    $names = array_keys($this->vars);
    $values = array_values($this->vars);
    array_walk($names, function (&$value, $key){
      $value = '{%'.$value.'}';
    });
    return str_replace($names, $values, $tpl);
  }

  function getParsed() {
    return $this->replaceVars($this->template);
  }

  function hasBlock($blockName) {
    return false;
  }

  function getBlock($blockName) {
    return null;
  }

  function hasVar($varName) {
    return array_key_exists($varName, $this->vars);
  }

  function setVar($varName, $varValue) {
    $this->vars[$varName] = $varValue;
  }

  function getVar($varName) {
    return $this->vars[$varName];
  }

  function reset() {
    $this->vars = [];
  }

}
