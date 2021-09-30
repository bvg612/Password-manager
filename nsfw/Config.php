<?php

namespace nsfw;

use Exception;
use nsfw\i18\NullLanguage;

require_once __DIR__ . '/GenericConfig.php';
require_once __DIR__ . '/i18/Language.php';
require_once __DIR__ . '/i18/AbstractLanguage.php';
require_once __DIR__ . '/i18/NullLanguage.php';

/**
 * Class Config
 *
 * @package nsfw
 *
 * @ property string $classPath
 * @ property string $projectRoot
 * @ property string $webroot directory on disk which represents the site root, visible on web
 * @ property string $webPath absolute path of website root url, without host
 * @ property string $webHost host name including port, without http(s)
 *
 * @ method static Config getInstance()
 * @ method static Config newInstance()
 */
class Config extends GenericConfig{

  public function __construct(GenericConfig $ac = null) {
    parent::__construct($ac);
    $this->initFromPhp(__DIR__ . '/default-config.php');
  }


  public function buildAbsoluteUrl($relative) {
    $absolutePath = $this->webPath;
    if($absolutePath != '/')
      $absolutePath .= '/';

    if(strlen($relative) > 0 && substr($relative, 0, 1) === '/') {
      $relative = substr($relative, 1);
    }
    $absolutePath .= $relative;
    return $absolutePath;
  }

  /**
   * Makes URL including hostname
   * @param string $relative path relative to webPath
   * @return string
   */
  public function buildCompleteUrl($relative) {
    $absolutePath = $this->buildAbsoluteUrl($relative);
    return 'http://'.$this->webHost.$absolutePath;
  }
}
