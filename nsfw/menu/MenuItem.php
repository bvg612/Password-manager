<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 27-05-16
 * Time: 6:04 PM
 */

namespace nsfw\menu;


use nsfw\template\DisplayObject;

interface MenuItem {
  public function isSelected();
}
