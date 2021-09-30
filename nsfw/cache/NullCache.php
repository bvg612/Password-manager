<?php

namespace nsfw\cache;

use nsfw\Singleton;

class NullCache implements Cache{
  protected static $instance;

  /**
   * NullCache constructor.
   * @param null $param
   */
  public function __construct($param = null) {
  }


  /**
   * This method is just optimisation to use a single dummy object for everything
   *
   * @return NullCache
   */
  public static function createInstance() {
    if(empty(self::$instance))
      self::$instance = new NullCache();
    return self::$instance;
  }

  public function put($name, $object, $ttl = 0) {
  }

  public function get($name, $default = null) {
    return $default;
  }

  public function getSet($name, callable $callback, array $params = [], $ttl = null) {
    return call_user_func_array($callback, $params);
  }


  public function setExpired($name) {
  }

  public function saveCache() {
  }

  public function clear($namespace = false) {
  }

  public function gc($clear = false) {
  }

  public function getInstance($namespace) {
    return $this;
  }


}
