<?php

namespace nsfw\validators;

use Exception;
use nsfw\errors\iErrorReporter;
use nsfw\errors\ErrorReporter;
use nsfw\forms\FormField;
use nsfw\Singleton;

SimpleValidator::register('nsfw\validators\ValidatorRequired','required');
SimpleValidator::register('nsfw\validators\ValidatorInteger','integer');
SimpleValidator::register('nsfw\validators\ValidatorEmail','email');
SimpleValidator::register('nsfw\validators\ValidatorPositive','positive');
SimpleValidator::register('nsfw\validators\ValidatorNumber','number');
/**
 * Class SimpleValidator
 *
 * Represent validators that do not need any input other than value. A simple validator is "email" for example. "range"
 * validator is not simple because it needs min/max values.
 *
 * @package nsfw\forms
 *
 * @method static SimpleValidator getInstance();
 */
class SimpleValidator extends Singleton{
  private static $validatorClasses = [];

  private $validatorPool = [];
  private $errorReporter;

  /**
   * SimpleValidator constructor.
   */
  public function __construct() {
    $this->errorReporter = new ErrorReporter();
  }


  public static function register($class, $validator){
    $validator = strToLower($validator);
    assert(preg_match('/^[a-z0-9._-]+$/', $validator));
    self::$validatorClasses[$validator] = $class;
  }

  /**
   * @param string $validatorName
   * @return bool|mixed
   */
  public static function getValidatorClass($validatorName) {
    if(empty(self::$validatorClasses[$validatorName]))
      return false;
    return self::$validatorClasses[$validatorName];
  }

  /**
   * @param string $validatorName
   * @return bool|mixed
   */
  public static function validatorExists($validatorName) {
    return self::getValidatorClass($validatorName) !== false;
  }

  /**
   * @param string $validator
   * @param mixed $value
   * @param iErrorReporter $errorReporter
   * @param string|FormField $object The object (name) being validated. If it's string - it is the name of the object
   *   (form field). If it's an object the name will be taken from $object->getName();
   *
   * @return bool
   *
   * @throws Exception
   */
  function validate($validator, $value, iErrorReporter $errorReporter = null, $object = 'value') {
    $validator = strToLower($validator);
    $v = $this->getValidatorInstance($validator);
    if(empty($errorReporter))
      $errorReporter = $this->errorReporter;
    $v->setErrorReporter($errorReporter);

    if(is_object($object)) {
      if($object instanceof FormField) {
        $name = $object->getLabel();
        if(empty($name))
          $name = $object->getName();
        $v->setFieldName($name);
      }
    } else if(is_string($object)) {
      $v->setFieldName($object);
    }

    $result = $v->validateValue($value);

    return $result;
  }

  /**
   * @param $validatorName
   * @return Validator
   * @throws Exception
   */
  public function getValidatorInstance($validatorName) {
    $validatorName = strToLower($validatorName);
    if(!isset($this->validatorPool[$validatorName])) {
      $class = self::getValidatorClass($validatorName);

      if(empty($class))
        throw new Exception('No class is registered for ' . $validatorName . ' validator');

      $this->validatorPool[$validatorName] = new $class();
    }
    return $this->validatorPool[$validatorName];
  }

  /**
   * @return iErrorReporter
   */
  public function getErrorReporter() {
    return $this->errorReporter;
  }

  /**
   * @param iErrorReporter $errorReporter
   */
  public function setErrorReporter(iErrorReporter $errorReporter) {
    $this->errorReporter = $errorReporter;
  }

  /**
   * @return array
   */
  public function getErrors() {
    foreach($this->validatorPool as $validator) {
      /** @var Validator $validator */
      $this->errorReporter->addErrors($validator->getErrorReporter()->getClearErrors());
    }
    return $this->errorReporter->getErrors();
  }

  /**
   * @return array
   */
  public function getClearErrors() {
    $this->getErrors(); // this will collect errors from validators

    return $this->errorReporter->getClearErrors();
  }

}
