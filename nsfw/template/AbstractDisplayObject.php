<?php
/**
 * User: npelov
 * Date: 04-07-17
 * Time: 3:39 PM
 */

namespace nsfw\template;


abstract class AbstractDisplayObject implements DisplayObject {
  abstract function getHtml();

  function __toString() {
    return $this->getHtml();
  }

}
