<?php
/**
 * User: npelov
 * Date: 23-10-17
 * Time: 5:28 PM
 */

namespace nsfw\template;


class JustTextTemplate implements Template {
  use TemplateTraits;

  protected $content;

  /**
   * JustTextTemplate constructor.
   * @param string|DisplayObject|mixed $content
   */
  public function __construct($content = '') {
    $this->setContent($content);
  }

  function loadFromFile($file) {
    $this->content = file_get_contents($file);
  }

  /**
   * @return mixed
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * @param string|DisplayObject|mixed $content
   */
  public function setContent($content) {
    $this->content = (string) $content;
  }

  function getParsed() {
    return $this->content;
  }

  function setTemplate($template) {
    $this->setContent($template);
  }

  function getTemplate() {
    return $this->getContent();
  }


  function display() {
    echo $this->getParsed();
  }

  function hasBlock($blockName) {
    return false;
  }

  function getBlock($blockName) {
    return false;
  }

  function hasVar($varName) {
    return false;
  }

  function setVar($varName, $varValue) {
    if($varName == 'content')
      $this->setContent($varValue);
  }

  function getVar($varName) {
    if($varName == 'content')
      return $this->getContent();
    return '';
  }

  function reset() {
  }

  function getHtml() {
    return $this->getParsed();
  }

  function __toString() {
    return $this->getParsed();
  }



}
