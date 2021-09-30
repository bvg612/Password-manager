<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 09.04.2016
 * Time: 11:34 AM
 */

namespace nsfw\cache;


use Exception;

abstract class AbstractCache implements Cache{
  protected $defaultTtl = 86400;
  protected $namespace;
  public static $clones = [];

  /**
   * AbstractCache constructor.
   * @param string $namespace
   * @throws Exception
   */
  public function __construct($namespace) {
    $this->namespace = $namespace;
    if(!empty(self::$clones[$namespace]))
      throw new Exception('Already created with this namespace. Use getInstance()');
    self::$clones[$namespace] = $this;
    $this->initCache();
  }

  /**
   * Initialize/load cache. Can be used if cache is loaded on startup.
   */
  abstract function initCache();
  abstract public function put($name, $object, $ttl = null);
  abstract public function get($name, $default = null);
  abstract public function setExpired($name);

  /**
   * Closes cache/writes cache to disk
   */
  function closeCache() {
    $this->saveCache();
    unset(self::$clones[$this->namespace]);
  }

  public function getInstance($namespace ) {
    $clone = clone $this;
    self::$clones[$namespace] = $this;
    $clone->setNamespace($namespace);
    return $clone;
  }

  /**
   * @param string $namespace
   */
  public function setNamespace($namespace) {
    $this->namespace = $namespace;
  }

  /**
   * Requests value from cache. If it's not available call $callback and return the result
   * @param string $name
   * @param callable $callback
   * @param array $params parameters to be passed to callback
   * @param int|null $ttl time to live or null for default TTL
   * @return mixed
   */
  public function getSet($name, callable $callback, array $params = [], $ttl = null) {
    if(is_null($ttl))
      $ttl = $this->defaultTtl;
    $value = $this->get($name, null);
    if(!is_null($value))
      return $value;
    $value = call_user_func_array($callback, $params);
    $this->put($name, $value, $ttl);
    return $value;
  }


  /**
   * @return string
   */
  public function getNamespace(){
    return $this->namespace;
  }


}
