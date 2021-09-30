<?php
/**
 * User: npelov
 * Date: 22-10-17
 * Time: 1:44 PM
 */

namespace nsfw\controller;


use nsfw\template\AbstractDisplayObject;

/**
 * Class PageHeadBlock
 * @package nsfw\controller
 *
 * @method PageHeadFiles getBlock(string $name)
 */
class PageHeadBlock extends MultiBlock {

  /**
   * PageHeadBlock constructor.
   */
  public function __construct() {
    $this->addBlock('css', new CssFiles());
    $this->addBlock('js', $js = new JavascriptFiles());
  }

}
