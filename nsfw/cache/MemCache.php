<?php

namespace nsfw\cache;


/**
 * Class MemCache
 * Uses php module memcache to connect to memcached
 * @package nsfw\cache
 */
class MemCache extends AbstractCache{
  /** @var \MemCache */
  protected $memcache;
  protected $options = [
    'host'=>'127.0.0.1',
    'port'=>11211,
    'timeout'=>1,
  ];

  /**
   * MemCache constructor.
   * @param string $namespace
   * @param array $options
   */
  public function __construct($namespace, array $options) {
    $this->setOptions($options);
    parent::__construct($namespace);
  }

  /**
   * @param array $options Array of connect options 'host', 'port' - port or 0 for socket, 'timeout' - default is 1
   */
  protected function setOptions(array $options) {
    foreach($this->options as $name => $value) {
      if(array_key_exists($name, $options)) {
        $this->options[$name] = $options[$name];
      }
    }
  }

  function initCache() {
    $mc = $this->memcache = new \Memcache();
    $options = $this->options;
    $mc->connect(
      $options['host'],
      $options['port'],
      $options['timeout']
    );
  }

  function saveCache() {
  }

  function clear($namespace = false) {
    // ToDo: clear just a namespace, not the whole cache
    $this->memcache->flush();
  }

  function gc($clear = false) {
  }


  function put($name, $object, $ttl = null) {
    //flag could have MEMCACHE_COMPRESSED
    // ttl is max 2592000 (30 days)
    $this->memcache->set($name, [
      'data'=>$object
    ], 0, $ttl);
  }

  function get($name, $default = null) {
    $flags = 0;
    $cacheObject = $this->memcache->get($name, $flags);
    if(empty($cacheObject) || !is_array($cacheObject))
      return $default;
    if(!array_key_exists('data', $cacheObject))
      return $default;
    return $cacheObject['data'];
  }

  public function setExpired($name) {
    $this->memcache->delete($name);
  }

}
