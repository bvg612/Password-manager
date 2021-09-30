<?php

namespace nsfw;

use ReflectionClass;

/**
 * Class Singleton
 *
 * You can have public static factory() method in inheritor class which creates an instance with params.
 * factory() method is required if you have more than 3 parameters in constructor
 *
 * You should declare getInstance() and optionally newInstance() in your class' phpDoc
 *
 * @package nsfw
 */
class Singleton {
  /** @var array */
  private static $instances = [];

  protected static function _createInstance(array $args) {
    $calledClass = get_called_class();
    if(method_exists($calledClass, 'factory')) {
      self::$instances[$calledClass] = call_user_func_array($calledClass . '::factory', $args);
    } else {
      $argc = count($args);
      switch($argc) {
        case 0: self::$instances[$calledClass] = new static (); break;
        case 1: self::$instances[$calledClass] = new static ($args[0]); break;
        case 2: self::$instances[$calledClass] = new static ($args[0], $args[1]); break;
        case 3: self::$instances[$calledClass] = new static ($args[0], $args[1], $args[2]); break;
        default: throw new Exception('If you have more than 3 params in Singelton class you need to use factory() method');
      }

      //$reflect  = new ReflectionClass($calledClass);
      //self::$instances[$calledClass] = $reflect->newInstanceArgs($args);
    }
    return self::$instances[$calledClass];
  }

  /**
   * @return Singleton|mixed
   */
  public static function getInstance() {
    $calledClass = get_called_class();
    if(empty(self::$instances[$calledClass])) {
      self::_createInstance(func_get_args());
    }
    return self::$instances[$calledClass];
  }

  /**
   * @return Singleton
   */
  public static function newInstance() {
    $calledClass = get_called_class();
    if(isset(self::$instances[$calledClass]))
      unset(self::$instances[$calledClass]);

    return static::_createInstance(func_get_args());
  }

  /**
   * @return bool
   */
  public static function hasInstance() {
    return !empty(self::$instances[get_called_class()]);
  }

  protected function __construct() {
  }

  protected function __clone() {
  }

  protected function __wakeup() {
  }

}
