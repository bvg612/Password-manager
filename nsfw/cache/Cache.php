<?php

namespace nsfw\cache;


/**
 * Interface Cache
 *
 * The implementation must implement a way to set application name if the cache is shared between multiple applications
 *
 * namespace is a name for the specific section of the application. For exampe it can be 'users' if you want to cache
 * users by id so it doesn't mix with other object ids.
 *
 * @package nsfw\cache
 */
interface Cache {

  /**
   * If cache is not stored on put() this will store the cache contents in its storage
   */
  public function saveCache();

  /**
   * Sets all cache to expired. Depending on implementation may or may not remove entries from cache. To ensure
   * removing entries from cache gc() must be called
   *
   * @param bool|string $namespace false to clear all cache, true (default) to clear current namespace or
   *   specify namespace string to clear specific namespace
   */
  public function clear($namespace = true);

  /**
   * Garbage collection - removes expired cache entries
   *
   * @param bool $clear - true if cache is cleared before gargage collection
   */
  public function gc($clear = false);

  /**
   * Use this to change namespace while getting instance.
   * @param string $namespace
   * @return Cache
   */
  public function getInstance($namespace);

  /**
   * @param string $name Stores variable in cache
   * @param mixed $object
   * @param int|null $ttl
   */
  public function put($name, $object, $ttl = null);

  /**
   * Fetches item from cache. Returns $default if the item is not in cache or expired
   * @param string $name
   * @param mixed|null $default
   * @return mixed
   */
  public function get($name, $default = null);

  /**
   * Fetches item from cache. If the item is not in cache or expired uses $callback to get value, then stores it in
   * cache and returns it
   * @param string $name
   * @param callable $callback
   * @param array $params Parameters array for $callback
   * @param int|null $ttl Time to live in seconds. Implementation must use default ttl when this parameter is null
   * @return mixed
   */
  public function getSet($name, callable $callback, array $params = [], $ttl = null);

  /**
   * Forces the cache to expire
   * @param $name
   */
  public function setExpired($name);

}
