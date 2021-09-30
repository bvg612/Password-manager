<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 12:25 PM
 */

namespace nsfw\modules;


abstract class ModuleConfig {
  /**
   * @return array
   */
  abstract function getConfig();
  abstract function init();
}
