<?php
/**
 * User: npelov
 * Date: 22-10-17
 * Time: 1:46 PM
 */

namespace nsfw\controller;


class MultiBlock extends PageBlock {
  protected $blocks = [];

  /**
   * @param string $name
   * @param PageBlock $pageBlock
   */
  public function addBlock($name, PageBlock $pageBlock) {
    $this->blocks[$name] = $pageBlock;
  }

  /**
   * @param string $name
   */
  public function removeBlock($name) {
    unset($this->blocks[$name]);
  }

  /**
   * @param string $name
   * @return PageBlock
   */
  public function getBlock($name) {
    return $this->blocks[$name];
  }

  function getHtml() {
    $content = '';
    foreach($this->blocks as $name=>$block) {
      $content .= $block->getHtml();
    }
    return $content;
  }

}
