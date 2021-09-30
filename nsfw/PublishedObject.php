<?php
/**
 * User: npelov
 * Date: 13-06-17
 * Time: 4:54 PM
 */

namespace nsfw;


/**
 * Class PublishedObject
 *
 * You select which fields to be published.
 *
 * @package nsfw
 * @see SimpleObject, nsObject
 */
class PublishedObject {
  /** @var array key is property name, value is readonly flag */
  protected $published = [];

  protected function publishFields($names, $readonly = false) {
    if(is_string($names))
      $names = [$names=>$readonly];
    foreach($names as $name) {
      $this->published[$name] = $readonly;
    }
  }

  function __isset($name) {
    if(property_exists(get_class($this), $name) && array_key_exists($name, $this->published)){
      return true;
    }
    return false;
  }


  function __get($name) {
    if(array_key_exists($name, $this->published)) {
      $method = 'get'.$name;
      if(method_exists($this, $method) && is_callable($this, $method)) {
        return $this->$method($name);
      }

      if(property_exists(get_class($this), $name)){
        return $this->$name;
      }
    }
    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
    return null;
  }

  function __set($name, $value) {
    if(array_key_exists($name, $this->published)) {
      if($this->published[$name]) {
        throw new \Exception('Magic property '.get_class($this).'::'.$name.' is read only. '.
          'To make it writable use false when calling publishFields().');
      }

      $method = 'set'.$name;
      if(method_exists($this, $method) && is_callable($this, $method)) {
        $this->$method($name, $value);
        return;
      }
      if(property_exists(get_class($this), $name) ){
        $this->$name = $value;
        return;
      }
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __set(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
  }

  public function __unset($name) {
    // Do not allow unset.
    $trace = debug_backtrace();
    trigger_error(
      'Cannot unset magic property PublishedObject__unset(' . $name . '): '.
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
  }

}
