<?php

namespace nsfw\cache;


/**
 * Class SimpleFileCache
 * @package nsfw\cache
 * @ToDo: Put some time of signature in front of the cache files to prevent loading of files like /etc/passwd
 */
class SimpleFileCache extends AbstractCache{
  protected static $data = [];
  protected static $namespaceChanged = [];
  protected $cacheDir;

  /**
   * SimpleFileCache constructor.
   * @param string $namespace Cache namespace
   * @param string $cacheDir Directory where namespace files will be stored
   */
  public function __construct($namespace, $cacheDir) {
    $this->cacheDir = $cacheDir.'/sfc'; // from SimpleFileCache
    @mkdir($this->cacheDir, 0700, true);
    parent::__construct($namespace);
  }

  function __destruct() {
    $this->closeCache();
  }

  private function getCacheFilename($namespace = false) {
    if(empty($namespace))
      $namespace = $this->namespace;
    return $this->cacheDir . '/' . $namespace . '.dat';
  }

  /**
   * @param string $namespace
   * @return SimpleFileCache|AbstractCache|Cache
   */
  public function getInstance($namespace) {
    /** @var SimpleFileCache $instance */
    $instance = parent::getInstance($namespace);
    $instance->loadNamespace();
    return $instance;
  }

  protected function loadNamespace() {
    self::$data[$this->namespace] = [];
    $cacheData = false;
    if(file_exists($this->getCacheFilename()))
      $cacheData = @unserialize(file_get_contents($this->getCacheFilename()));
    if(is_array($cacheData)) {
      self::$data[$this->namespace] = $cacheData;
    }
  }

  /**
   * @param bool|string $namespace true to store current namespace, string to store specific namespace,
   *    false to store all namespaces
   */
  protected function storeNamespace($namespace = true) {
    if($namespace === true)
      $namespace = $this->namespace;

    if($namespace === false) {
      $namespaces = array_keys(self::$data);
    } else {
      $namespaces = [$namespace];
    }

    foreach($namespaces as $ns) {
      if(!array_key_exists($ns, self::$namespaceChanged))
        self::$namespaceChanged[$ns] = true;

      if(!self::$namespaceChanged[$ns])
        continue;

      $cacheFile = $this->getCacheFilename($ns);
      if(!empty(self::$data[$ns])) {
        file_put_contents($cacheFile, serialize(self::$data[$ns]));
      } else {
        if(file_exists($cacheFile)) {
          unlink($cacheFile);
        }
      }
      self::$namespaceChanged[$ns] = false;
    }
  }

  function initCache() {
    $this->loadNamespace();
  }

  /**
   * Saves only current namespace!!!
   */
  function saveCache() {
    $this->storeNamespace();
  }

  function closeCache() {
    parent::closeCache();
    foreach(self::$data as $namespace=>$data) {
      $this->storeNamespace($namespace);
    }
  }

  function clear($namespace = true) {

    if($namespace === true) {
      self::$data[$this->namespace] = [];
      $this->storeNamespace($this->namespace);
    } else if(is_string($namespace)) {
      self::$data[$namespace] = [];
      $this->storeNamespace($namespace);
    } else {
      assert($namespace === false);
      $namespaces = array_keys(self::$data);
      foreach($namespaces as $ns) {
        self::$data[$ns] = [];
      }
      $this->storeNamespace(false);
      //ToDo: delete all cache files from cache dir. Make some safety for this
    }
  }

  public function gc($clear = false) {
    if($clear)
      $this->clear();

    trigger_error('Not implemented', E_USER_WARNING);
    // TODO: Implement gc() method.
  }


  public function put($name, $object, $ttl = null) {
    if(is_null($ttl))
      $ttl = $this->defaultTtl;
    self::$data[$this->namespace][$name] = [
      'ttl' => time()+$ttl,
      'value' => $object
    ];
    self::$namespaceChanged[$this->namespace] = true;
  }

  public function get($name, $default = null) {
    if(!array_key_exists($name, self::$data[$this->namespace]))
      return $default;

    $cacheObj = self::$data[$this->namespace][$name];
    if($cacheObj['ttl'] < time()) {
      $this->setExpired($name);
      return $default;
    }

    return $cacheObj['value'];
  }

  public function setExpired($name) {
    unset(self::$data[$this->namespace][$name]);
    self::$namespaceChanged[$this->namespace] = true;
  }

  /**
   * @codeCoverageIgnore
   */
  public function dump() {
    var_dump(self::$data);
  }

}
