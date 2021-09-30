<?php
/**
 * User: npelov
 * Date: 23-06-17
 * Time: 12:05 PM
 */

namespace nsfw\langeditor;


use nsfw\cache\Cache;
use nsfw\template\CascadedTemplate;

class Template extends CascadedTemplate {
  protected static $defaultConfig;
  public function __construct($template = '', Cache $cache = null) {
    parent::__construct($template, $cache);
  }

}

Template::setDefaultConfig(CascadedTemplate::getDefaultConfig());
