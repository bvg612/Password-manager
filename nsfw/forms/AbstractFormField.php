<?php

namespace nsfw\forms;

use Exception;
use nsfw\errors\iErrorReporter;
use nsfw\errors\NullErrorReporter;
use nsfw\validators\SimpleValidator;
use nsfw\validators\Validator;

/**
 * Class AbstractFormField
 * @package nsfw\forms
 *
 * @property string $value
 * @property string $name
 * @property string $label
 */
abstract class AbstractFormField implements FormField{

  protected static $defaultAttributes = [];

  /** @var bool */
  protected $required = false;

  /** @var string */
  protected $label;

  /** @var string|array */
  protected $value = '';

  /** @var string */
  protected $name;

  /** @var null|string */
  protected $default = '';

  /** @var array */
  protected $attributes = [];

  /** @var array */
  protected $validators = [];

  /** @var SimpleValidator */
  protected $simpleValidator;

  protected static $formFieldClasses = [];

  protected $paramOrder = 'P';

  /** @var bool|ValidatorBypass|callable  */
  protected $validationBypass = false;

  /** @var bool Stores valid state after validate() */
  protected $valid = false;

  /** @var iErrorReporter */
  protected $errorReporter;

  public function __construct($name, $value = null, iErrorReporter $errorReporter = null) {
    $this->attributes = array_merge($this->attributes, self::$defaultAttributes);
    $this->name = $name;
    if(!is_null($value))
      $this->setValue($value);
    $this->simpleValidator = SimpleValidator::getInstance();
    if(empty($errorReporter))
      $errorReporter = NullErrorReporter::getInstance();
    $this->errorReporter = $errorReporter;
  }

  /**
   * Returns form field class for form field type
   * @param $type
   * @return bool|mixed
   */
  public static function getClass($type) {
    if(empty(static::$formFieldClasses[$type])) {
      return false;
    }
    return static::$formFieldClasses[$type];
  }

  /**
   * @param string $type
   * @param string $class
   */
  public static function register($type, $class) {
    static::$formFieldClasses[$type] = $class;
  }


  /**
   * @return boolean
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * @param boolean $required
   */
  public function setRequired($required) {
    $this->required = $required;
  }

  public function getErrorReporter() {
    return $this->errorReporter;
  }

  public function setErrorReporter(iErrorReporter $errorReporter) {
    $this->errorReporter = $errorReporter;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return string
   */
  public function getLabelHtml() {
    $id = $this->getAttribute('id');
    if(empty($id))
      return $this->label;
    return '<label for="'.$id.'" >'.htmlspecialchars($this->label).'</label>';
  }

  /**
   * @param string $label
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * @param string $value
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }


  /**
   * @param $attr
   * @return string
   */
  public function getAttribute($attr) {
    // ToDo: does this need to be an exception?
    if(empty($this->attributes[$attr]))
      return false;
    return $this->attributes[$attr];
  }

  /**
   * @param string $attr
   * @param string $value
   */
  public function setAttribute($attr, $value) {
    $this->attributes[$attr] = $value;
  }

  public function getAttributesHtml() {
    $html = '';
    foreach($this->attributes as $attribute=>$value) {
      $html .= ' '.$attribute.'="'.htmlspecialchars($value).'"';
    }
    return $html;
  }

  protected function _addValidator(Validator $v, $first = false){
    if(get_class($v) === 'ValidatorRequired')
      $this->required = true;
    if($first)
      array_unshift($this->validators, $v);
    else
      $this->validators[] = $v;
  }

  /**
   * @param Validator|string $validator
   * @param bool $first
   * @throws Exception
   */
  public function addValidator($validator, $first = false){
    if(is_object($validator)){
      $this->_addValidator($validator, $first);
      $name = $this->getLabel();
      if(empty($name))
        $name = $this->getName();
      $validator->setFieldName($name);
    }else if(is_string($validator)){
      $explode = explode(',', $validator);
      foreach($explode as $v){
        $v = trim($v);
        if(empty($v))
          continue;
        $this->_addValidator($this->simpleValidator->getValidatorInstance($v), $first);
      }
    }else{
      throw new Exception('Validator should be instance of Validator or string');
    }
  }

  public function insertValidator($validator) {
    $this->addValidator($validator, true);
  }

  public function getValidators() {
    return $this->validators;
  }

  /**
   * @param string $validator
   * @return bool
   * @throws Exception
   */
  private function simpleValidate($validator) {
    return $this->simpleValidator->validate(
      $validator,
      $this->value,
      $this->errorReporter,
      $this
    );
  }

  public function validate() {
    $this->valid = true;
    if($this->required) {
      $this->valid = $this->simpleValidate('required');
//      $valid = !$this->isEmpty();

      /*
      if($this->value === '' || is_null($this->value) || $this->value === false)
        return false;
      */
    } else {
      if($this->value == '')
        return true;
    }

    $vb = $this->validationBypass;
    if($vb instanceof ValidatorBypass){
      if($vb->skipValidation($this))
        return true;
    }else if(is_callable($vb, true)){ // syntax only is checked, actual checking is when adding bypass
      //if callback should return true for skip
      if(call_user_func($vb, $this))
        return true;
    }
    unset($vb);

    foreach($this->validators as $validator){
      if($validator instanceof Validator){
        $valid = $validator->validateValue($this->value);
        //echo $this->name.' = '.$this->value.': '.get_class($validator).' -> '.($valid?'valid':'not valid').'<br />';
        if(!$valid){
          $this->valid = false;
          $this->errorReporter->addErrors($validator->getErrorReporter());
          // stop on error
          return $this->valid;
        }
      }else if(is_string($validator)){
        $valid = $this->simpleValidate($validator);
        //echo $this->name.' = '.$this->value.': Static::'.$validator.' -> '.($valid?'valid':'not valid').'<br />';
        if(!$valid){
          $this->valid = false;
          // stop on error
          return $this->valid;
        }
      }
    }

    return  $this->valid;
  }

  public function getFromPost(){
    $this->value = $value = getParam($this->name, $this->default, strToUpper($this->paramOrder));
  }

  public function setParamOrder($order) {
    $this->paramOrder = $order;
  }

  public function getParamOrder() {
    return $this->paramOrder;
  }

  public function addClass($newClass){
    $classStr = '';
    $classes = [];
    if(array_key_exists('class', $this->attributes)) {
      $classes = explode(' ', $this->attributes['class']);
    }
    $classes[] = $newClass;
    foreach($classes as $className) {
      if(!empty($classStr))
        $classStr .= ' ';
      $classStr .= $className;
    }
    $this->attributes['class'] = $classStr;
    return $classStr;
  }

  public function removeClass($delClass){
    $classStr = '';
    $classes = [];
    if(array_key_exists('class', $this->attributes)) {
      $classes = explode(' ', $this->attributes['class']);
    }
    foreach($classes as $className) {
      if($className == $delClass)
        continue;
      if(!empty($classStr))
        $classStr .= ' ';
      $classStr .= $className;
    }
    if(empty($classStr))
      unset($this->attributes['class']);
    else
      $this->attributes['class'] = $classStr;
    return $classStr;
  }

  public function isEmpty() {
    if(strval($this->value) === "0")
      return false;
    return empty($this->value);
  }

  public function __isset($name) {
    switch($name) {
      case 'value':
      case 'name':
      case 'label':
        return true;
      default: return false;
    }
  }

  public function __get($name) {
    switch($name) {
      case 'value':
        return $this->getValue();
      case 'label':
      case 'name':
        return $this->$name;
      default: throw new Exception('Invalid field "'.$name.'"');
    }
  }

  public function __set($name, $value) {
    switch($name) {
      case 'value': $this->setValue($value);
      case 'name':
      case 'label':
        $this->$name = $value;
        break;
      default: throw new Exception('Invalid field "'.$name.'"');
    }

  }

  public function __toString() {
    return $this->getValue();
  }

}

AbstractFormField::register('text', 'nsfw\forms\TextField');
AbstractFormField::register('textarea', 'nsfw\forms\TextareaField');
AbstractFormField::register('password', 'nsfw\forms\PasswordField');
AbstractFormField::register('hidden', 'nsfw\forms\HiddenField');
AbstractFormField::register('boolean', 'nsfw\forms\BooleanField');
AbstractFormField::register('checkbox', 'nsfw\forms\CheckboxField');
AbstractFormField::register('radio', 'nsfw\forms\RadioField');
AbstractFormField::register('display', 'nsfw\forms\DisplayField');
AbstractFormField::register('select', 'nsfw\forms\SelectField');
AbstractFormField::register('image', 'nsfw\forms\ImageField');
AbstractFormField::register('multiselect', 'nsfw\forms\MultiSelectField');
AbstractFormField::register('file', 'nsfw\forms\FileField');
