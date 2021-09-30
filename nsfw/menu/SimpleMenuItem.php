<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 27-05-16
 * Time: 6:01 PM
 */

namespace nsfw\menu;


class SimpleMenuItem extends AbstractMenuItem {
  /** @var string */
  public $type;
  public $alt = '';


  public function isSelected() {
    if(!empty($_SERVER['REQUEST_URI'])) {
      $linkLen = strlen($this->link);
      return substr($_SERVER['REQUEST_URI'], 0, $linkLen) == $this->link;
    }
    return false;
  }

}
