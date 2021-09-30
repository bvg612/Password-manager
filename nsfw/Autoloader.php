<?php

namespace nsfw;

use Exception;
use nsfw\cache\Cache;
use nsfw\exception\ClassNotFoundException;

require_once __DIR__ . '/cache/Cache.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Singleton.php';

/**
 * Class Autoloader
 * @package nsfw
 *
 * @method static Autoloader getInstance
 */
class Autoloader extends Singleton{

  /** @var Config  */
  private $config;
  /** @var  Cache */
  private $cache;

  /** @var array */
  private $classPath = [];

  private $psr4 = [];

  public function __construct() {
    parent::__construct();
    $this->config = Config::getInstance();

    $this->addClassRoot($this->config->classPath.'/nsfw');

  }

  /**
   * @param string $dir
   * @param string $prefix
   */
  public function addClassRoot($dir, $prefix = '') {
    if(!empty($prefix)) {
      $namespaceRoot = $prefix;
    } else {
      $namespaceRoot = basename($dir);
    }
    $this->classPath[$namespaceRoot] = $dir;
  }

  /**
   * @param $namespace
   * @param $dir
   */
  public function addPsr4($namespace, $dir) {
    $this->psr4[$namespace] = $dir;
  }

  public function removeClassRoot($namespaceRoot) {
    unset($this->classPath[$namespaceRoot]);
  }

  /**
   * @param Cache $cache
   */
  public function setCache(Cache $cache) {
    $this->cache = $cache;
  }

  public function autoload($fullClassName) {

    if($this->autoloadPsr4($fullClassName))
      return;

    $relativeClassPath = explode('\\', $fullClassName);
    $rootNamespace = array_shift($relativeClassPath);
    $className = array_pop($relativeClassPath);

    if(!array_key_exists($rootNamespace, $this->classPath))
      return false;

    $namespaceRootDir = $this->classPath[$rootNamespace];

    $path = $namespaceRootDir.'/'.implode('/', $relativeClassPath);
    $includeFile = $path.'/'.$className.'.php';

    if(!is_file($includeFile)) {
      if($className == 'User')
        throw new ClassNotFoundException($fullClassName, 'File not found while trying to load '.$fullClassName.': '.$includeFile);
      return false;
    }

    safeInclude($includeFile);
    if(!class_exists($fullClassName, false) && !interface_exists($fullClassName, false) && !trait_exists($fullClassName, false))
      throw new ClassNotFoundException($fullClassName, 'Class "'.$fullClassName.'" not found in file "'.$includeFile.'"');

    return true;
  }

  private function autoloadPsr4($fullClassName) {
    if(empty($this->psr4))
      return false;

    foreach($this->psr4 as $namespace=>$dir) {

      if(strpos($fullClassName, $namespace) === 0) {
        // found
        //remove namespace prefix
        $psr4ClassName = substr($fullClassName, strlen($namespace));
        $classFile = $dir.str_replace('\\', '/', $psr4ClassName).'.php';
        if(!file_exists($classFile))
          throw new Exception('File "' . $classFile . '" for class "' . $fullClassName . '" does not exist.', E_USER_ERROR);

        require_once $classFile;

        return true; // got it
      }
    }
    return false;
  }


  public function register(){
    spl_autoload_register(array($this, 'autoload'));
  }

  public function unregister() {
    spl_autoload_unregister(array($this, 'autoload'));
  }


}
