<?php

namespace nsfw\template;


use Exception;
use nsfw\cache\Cache;
use nsfw\cache\NullCache;

/**
 * Class TemplateConfig
 *
 * Template path configuration:
 * 1. mod_rewrite is not used, template in subdirectory of $config::
 *
 * 2.
 *
 * @package nsfw\template
 */
class TemplateConfig {

  public static $debug = false;

  /** @var string Main template directory */
  public $mainDir;

  /** @var string Subtemplate directory. False if it's only one template directory */
  public $subtemplateDir = false;

  /** @var boolean */
  public $reportUndefinedVariables = false;

  /** @var Cache */
  public $cache;


  /**
   * @var string Absolute url path to the root of the site. No trailing slash. A file in webroot should be visible
   * under this url. Example: '/gallery' or '/'
   */
  public $webPath = '/';

  /**
   * @var array File types that are loaded through template. The rest will be output as-is
   */
  public $parsedFileTypes = ['css', 'js', 'html', 'htm'];

  /**
   * @var bool
   * @ToDo: Make examples how to use mod_rewrite
   */
  public $useModRewrite = false;

  /**
   * @var string|bool  When using mod_rewrite - this is the templates directory. If using loader script - false
   *
   * if using mod_rewrite we need to know what directory translates to $tplWebPath in order to translate relative tpl
   * path to absolute web path
   * if not using mod_rewrite ?!?
   *
   * @see ResourceLoader
   */
  public $tplRootDir = false;

  /**
   * @var string|bool Template web path, relative to web root (true path or mod_rewrite redirected). <b>False</b> if template
   *  is <b>not visible</b> on web (bad idea)
   * @see ResourceLoader
   */
  public $tplWebPath = 'tpl';

  /**
   * @var string resource loader script web path relative to web root
   * @see ResourceLoader
   */
  public $loaderScript = 'rl.php';

  /** @var int Default TTL for template cache. */
  public $defaultTtl = 3600; // 1h


  /**
   * CascadedTemplateConfig constructor.
   */
  public function __construct() {

    $tplDir = dirname(dirname(dirname(__DIR__))).'/tpl/default';
    $this->mainDir = $tplDir;
    $this->cache = new NullCache();
  }

  /**
   * Use this setter  OR setLoaderScript(). Never both!
   * @param string $tplRootDir directory that translates to $tplWebPath
   * @param string|bool $tplWebPath path that mod_rewrite redirects to the loader script (see ResourceLoader class)
   * @throws Exception
   * @see ResourceLoader
   */
  public function setTplPath($tplRootDir, $tplWebPath = false) {
    assert(!empty($tplRootDir) && is_string($tplRootDir));
    if(substr($tplWebPath, 0, 1) == '/')
      throw $this->getRelativePathException(__METHOD__);
    $this->tplWebPath = $tplWebPath;
    $this->tplRootDir = $tplRootDir;
  }

  /**
   * Use this setter  OR setTplPath(). Never both!
   * @param string $scriptPath The path to the resource loader script, relative to $webPath (see ResourceLoader class)
   * @throws Exception
   * @see ResourceLoader
   */
  public function setLoaderScript($scriptPath) {
    if(substr($scriptPath, 0, 1) == '/')
      throw $this->getRelativePathException(__METHOD__);
    $this->loaderScript = $scriptPath;
  }

  public function isUsingModRewrite() {
    return $this->tplRootDir !== false;
  }

  public function isAParsedResource($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, $this->parsedFileTypes);
  }

  public function getRelativePathException($func) {
    return new Exception('Second argument of '.$func.'() must be a relative path (no leading slash)');
  }

}
