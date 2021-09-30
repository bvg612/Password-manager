<?php
/**
 * User: npelov
 * Date: 13-05-17
 * Time: 11:42 AM
 */

namespace nsfw;


use Exception;

/**
 * Class nsObject
 *
 * A data container
 *
 * See get() and set() methods for details
 *
 * @package nsfw
 */
class nsObject {

  /** @var bool allows adding new fields through __set() */
  protected $allowAddNewFields = false;
  protected $fields = [];
  protected $readonly = [];

  /**
   * Property GET priority:
   * 1. method get<name>
   * 2. $this->fields['<name>']
   * 3. private property $this-> <name><br />

   * @param string$name
   * @return mixed
   */
  public function get($name) {

    $method = 'get'.$name;
    if(method_exists($this, $method))
      return call_user_func([$this, $method]);
    if(array_key_exists($name, $this->fields))
      return $this->fields[$name];

    //if(property_exists(get_class($this), $name)){
    if(property_exists($this, $name)){
      return $this->$name;
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
    return null;
  }

  function __get($name) {
    return $this->get($name);
  }

  protected function addField($name, $value = null, $readonly = false) {
    $this->fields[$name] = $value;
    if($readonly)
      $this->readonly[$name] = true;
  }

  protected function setReadonly($name){
    $this->readonly[$name] = true;
  }

  /**
   *
   * property set priority:<br />
   * 1. method set<name>, but only if get<name> exists!
   * 2. $this->fields['<name>'], but only if not set as $this->readonly['<name>'] and
   *    if $this->allowAddNewFields and it doesn't already exist an exception is thrown
   * 3. private property $this-> <name>
   *
   * @param string $name
   * @param mixed $value
   * @throws Exception
   *
   */
  function set($name, $value) {

    $method = 'get'.$name;
    if(method_exists($this, $method)) {
      //call_user_func([$this, $method], $value); // why?

      $method = 'set' . $name;
      if(method_exists($this, $method)) {
        call_user_func([$this, $method], $value);
        return;
      } else {
        throw new Exception('Magic property '.$name.' is read only (has only getter and not setter)');
      }
    }

    if(array_key_exists($name, $this->readonly))
      throw new Exception('Magic property '.$name.' is read only (via setReadOnly()');

    //if(property_exists(get_class($this), $name)) {
    if(property_exists($this, $name)) {
      $this->$name = $value;
      return;
    }

    if(array_key_exists($name, $this->fields)) {
      $this->fields[$name] = $value;
      return;
    }else {
      if($this->allowAddNewFields)
        $this->fields[$name] = $value;
      return;
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __set(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
  }

  public function __set($name, $value) {
    $this->set($name, $value);
  }


  function __isset($name) {
    $method = 'get'.$name;
    if(method_exists($this, $method))
      return true;

    if(array_key_exists($name, $this->fields))
      return true;

    return false;
  }

  function __unset($name) {
    $method = 'get'.$name;
    if(method_exists($this, $method))
      throw new Exception('Cannot unset this magic property because it uses function getter/setter');

    if(array_key_exists($name, $this->fields))
      unset($this->fields[$name]);

    $trace = debug_backtrace();
    trigger_error(
      'Undefined magic property via __unset(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_WARNING);
  }

}
