<?php

namespace nsfw\template;

/**
 * Class TemplateCache
 *
 * Represents a parsed template
 *
 * @package nsfw\template
 */
class TemplateCache {
  /** @var string  */
  protected $template = '';

  /** @var string  */
  protected $name;

  /** @var array contains all the blocks (objects of TemplateCache) in template */
  protected $blocks = array();

  /**
   * TemplateCache constructor.
   * @param string $template
   * @param string $name
   * @param array $blocks
   */
  public function __construct($name, $template, array $blocks = []) {
    $this->name = $name;
    $this->template = $template;
    $this->blocks = $blocks;
  }


  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getTemplate() {
    return $this->template;
  }

  public function addBlock(TemplateCache $block){
    $this->blocks[$block->getName()] = $block;
  }

  /**
   * @param array $blocks
   */
  public function setBlocks(array $blocks){
    $this->blocks = [];
    foreach($blocks as $block){
      $this->addBlock($block);
    }
  }

  /**
   * @return array
   */
  public function getBlocks() {
    return $this->blocks;
  }

  public function getBlock($name){
    if(isset($this->blocks[$name]))
      return $this->blocks[$name];
    return false;
  }


  public function getBlockCount(){
    return count($this->blocks);
  }

  public function dump(){
    var_dump($this);
  }

}
