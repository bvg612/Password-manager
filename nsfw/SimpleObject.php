<?php

namespace nsfw;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class SimpleObject
 *
 * This class publishes protected properties. If you need to have protected properties, make them private with protected
 * getters and setters.
 *
 * @see PublishedObject, nsObject
 */
class SimpleObject {

  private $readonlyFields = [];

  /**
   * Accessor overloader.
   * Allows default getting of fields via $this->getVal(), or mediation via a
   * getParamName() method.
   *
   * @param string $field The field name.
   *
   * @return mixed The field.
   * @throws ReflectionException
   */
  public function __get($field) {
    if(!$this->hasField($field)){
      trigger_error('Undefined property(magic): '.get_class($this).'::$'.$field, E_USER_WARNING);
    }
    if(method_exists($this, $funcName = "get$field")) {
      return $this->$funcName();
    }
//    else {
      return $this->getField($field);
//    }
//    return null;
  }

  /**
   * Sets published field as readonly
   *
   * @param string $field Property name
   */
  protected function setReadonly($field) {
    $this->readonlyFields[$field] = true;
  }

  /**
   * Setter overloader.
   * Allows default setting of fields in $this->setVal(), or mediation via a
   * getParamName() method.
   *
   * @param string $field The field name.
   * @param mixed  $val   The field value.
   *
   * @throws ReflectionException
   */
  public function __set($field, $val) {
    if(!$this->hasField($field)){
      trigger_error('Undefined property(magic): '.get_class($this).'::$'.$field, E_USER_WARNING);
      return;
    }

    if(array_key_exists($field, $this->readonlyFields)) {
      trigger_error('Magic property ' . get_class($this) . '::$' . $field . ' is read-only', E_USER_WARNING);
      return;
    }

    if(method_exists($this, $funcName = "set$field")) {
      $this->$funcName($val);
    } else {
      $this->setField($field, $val);
    }
  }

  /**
   * Is-set overloader.
   * Will check to see if the given field exists on this object.  Calls the hasField() method.
   *
   * @param string $field The field name.
   *
   * @return boolean True if field exists
   * @throws ReflectionException
   */
  public function __isset($field) {
    if($this->hasField($field)) {
      return true;
    }
    return false;
  }

  /**
   * Get a field by it's name. This should be overloaded in child classes.
   *
   * @param string $field fieldname
   *
   * @return mixed
   */
  protected function getField($field) {
    return $this->$field;
  }

  /**
   * Set a fields value. This should be overloaded in child classes.
   * @param string $field The field name.
   * @param mixed $val The field value.
   */
  protected function setField($field, $val) {
    $this->$field = $val;
  }

  /**
   * Checks if a field exists on this object. This should be overloaded in child classes.
   *
   * @param string $field The field name
   *
   * @return boolean
   * @throws ReflectionException
   */
  public function hasField($field) {
    $funcNameSet = "set$field";
    $funcNameGet = "get$field";
    if(method_exists($this, $funcNameSet) || method_exists($this, $funcNameGet))
      return true;
    return property_exists($this, $field) && $this->isProtected($field);
  }

  /**
   * @param $propertyName
   *
   * @return bool
   *
   * @throws ReflectionException
   */
  protected function isProtected($propertyName) {
    $reflect = new ReflectionClass($this);
    $property = $reflect->getProperty($propertyName);
    return $property->isProtected();
  }

  /**
   * @return array
   * @throws ReflectionException
   */
  public function getData() {
    $reflect = new ReflectionClass($this);
    $properties = $reflect->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

    $result = [];
    foreach ($properties as $property) {
      $name = $property->getName();
      $result[$name] = $this->$name;
    }
    return $result;
  }
}
