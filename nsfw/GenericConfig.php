<?php

namespace nsfw;

require_once __DIR__ . '/Singleton.php';
require_once __DIR__ . '/generated/ConfigHint.php';

use Exception;
use nsfw\exception\FileNotFoundException;
use nsfw\generated\ConfigHint;

/**
 * Class AbstractConfig
 * @package nsfw
 *
 * @method static GenericConfig getInstance()
 */
 class GenericConfig extends Singleton implements ConfigHint{

  protected $vars = [];

  protected $readonly = false;
  protected $readonlyDebugInfo = '';

  public function __construct(GenericConfig $ac = null) {
    parent::__construct();
    if(!empty($ac))
      $this->setVars($ac->getVars());
  }

  /**
   * @return GenericConfig
   */
  public static function newInstance() {
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return parent::newInstance(func_get_args());
  }

  protected function initFromPhp($phpFile, $disableVarCreate = false, $clearVarsFirst = false) {
    if(!is_file($phpFile)) {
      throw new FileNotFoundException($phpFile);
    }

    /** @noinspection PhpIncludeInspection */
    require $phpFile;

    if(!isset($config))
      throw new Exception('$config variable needs to be set in "'.$phpFile.'"');

    if(!is_array($config))
      throw new Exception('$config must be an array');

    if($clearVarsFirst)
      $this->vars = [];

    foreach($config as $var=>$value) {
      if($disableVarCreate) {
        if(!array_key_exists($var, $this->vars))
          throw new Exception('Variable "' . $var . '" is not a valid config var for ' . get_class($this) . ' class');
      }
      $this->vars[$var] = $value;
    }
  }

   /**
    * @param string $phpFile a php file to include containing $config array
    */
  public function loadFromPhp($phpFile) {
    $this->initFromPhp($phpFile, true);
  }

  protected static function factory($args = []) {
    if(empty($args[0]))
      $args[0] = null;
    return new static($args[0]);
  }

  /**
   * @return boolean
   */
  public function isReadonly() {
    return $this->readonly;
  }

  /**
   * @param boolean $readonly
   */
  public function setReadonly($readonly) {
    $this->readonly = $readonly;
    $this->readonlyDebugInfo = Debug::getCallInfo(1);
  }


  protected function addVars($vars) {
    foreach($vars as $varName) {
      $this->vars[$varName] = null;
    }
  }

  public function __get($name) {
    return $this->getVar($name);
  }

  public function __set($name, $value) {
    $this->setVar($name, $value);
  }

  public function __isset($name) {
    return array_key_exists($name, $this->vars);
  }

  /**
   * @param $name
   * @return mixed
   */
  public function getVar($name) {
    if(!$this->__isset($name))
      trigger_error("Magic property ".__CLASS__."::$name does not exist!", E_USER_ERROR);
    return $this->vars[$name];
  }

  /**
   * @param string $name
   * @param mixed $value
   * @throws Exception
   */
  public function setVar($name, $value) {
    if($this->readonly) {
      throw new Exception('This config is set to readonly at '.$this->readonlyDebugInfo);
    }

    if(!$this->__isset($name))
      trigger_error("Magic property ".__CLASS__."::$name does not exist!", E_USER_ERROR);
    $this->vars[$name] = $value;
  }

  public function setVars($vars) {
    foreach($vars as $varName) {
      $this->vars[$varName] = null;
    }
  }

  public function getVars() {
    return $this->vars;
  }

  public function dump() {
    var_dump($this->vars);
  }

}
