<?php
/**
 * User: npelov
 * Date: 11-07-17
 * Time: 2:34 AM
 */

namespace nsfw\template;


/**
 * Class MockTemplate
 *
 * It's used by processors to pass var value to processMissingVar. I know it's stupid but I'm sick of it and I don't care!
 *
 * @package nsfw\template
 *
 */
class MockTemplate implements Template {
  private static $instance;
  protected $vars = [];

  private function __construct() {
  }

  /**
   * @param array|string $vars if this is string then $vars contains the var name and second parameter is value. if it's
   * array - it contains varName=>varValue paris
   * @param null $value if $vars is string this is the value
   */
  public static function getInstance($vars, $value = null) {
    if(empty(self::$instance)) {
      self::$instance = new static();
    }
    $instance = self::$instance;

    if(is_array($vars)) {
      $instance->vars = $vars;
    } else if(is_string($vars)){
      $instance->vars[$vars] = $value;
    }else {
      throw new \InvalidArgumentException('first parameter must be array or string');
    }
    return $instance;
  }

  function loadFromFile($filename) {
  }

  function getParsed() {
    return '';
  }

  function display() {
  }

  function hasBlock($blockName) {
    return false;
  }

  function getBlock($blockName) {
    return false;
  }

  function hasVar($varName) {
    return array_key_exists($varName, $this->vars);
  }

  function setVar($varName, $varValue) {
    $this->vars[$varName] = $varValue;
  }

  function getVar($varName) {
    if(!$this->hasVar($varName))
      return '';
    return $this->vars[$varName];
  }

  function getHtml() {
    return '';
  }

  function __toString() {
    return '';
  }

}
