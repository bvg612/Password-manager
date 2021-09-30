<?php
/**
 * User: npelov
 * Date: 23-05-17
 * Time: 6:34 PM
 */

namespace nsfw\i18;


abstract class SingeltonLanguage extends AbstractLanguage {
  /** @var array */
  private static $instances = [];

  protected static function _createInstance(array $args) {
    $calledClass = get_called_class();
    if(method_exists($calledClass, 'factory')) {
      self::$instances[$calledClass] = call_user_func_array($calledClass . '::factory', $args);
    } else {
      self::$instances[$calledClass] = new static();
    }
    return self::$instances[$calledClass];
  }

  /**
   * @return SingeltonLanguage
   */
  public static function getInstance() {
    $calledClass = get_called_class();
    if(empty(self::$instances[$calledClass])) {
      self::_createInstance(func_get_args());
    }
    return self::$instances[$calledClass];
  }

  /**
   * @return SingeltonLanguage
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

  public function __construct() {
    parent::__construct();
  }

}
