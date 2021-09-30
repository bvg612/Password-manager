<?php

namespace nsfw\validators;


use nsfw\errors\iErrorReporter;

class ValidatorRange extends AbstractValidator{
  /** @var int */
  protected $min;
  /** @var int */
  protected $max;

  /**
   * ValidatorLength constructor.
   * @param int $min
   * @param int $max
   * @param iErrorReporter $errorReporter
   */
  public function __construct($min, $max, iErrorReporter $errorReporter = null) {
    parent::__construct($errorReporter);
    $this->min = $min;
    $this->max = $max;
  }

  /**
   * @param int $value
   * @return int
   */
  function setMin($value){
    assert(is_numeric($value) && intval(strval($value)) == strval($value));
    if($this->max >= 0){
      if($value > $this->max){
        trigger_error('minLength('.$value.') can not be greater than maxLength('.$this->max.')', E_USER_WARNING);
      }
    }
    $prev = $this->min;
    $this->min = $value;
    return $prev;
  }

  /**
   * @param int $value
   * @return int
   */
  public function setMax($value){
    assert(is_numeric($value) && intval(strval($value)) == strval($value));
    if($this->min >= 0){
      if($value < $this->min){
        trigger_error('maxLength('.$value.') can not be less than minLength('.$this->min.')', E_USER_WARNING);
      }
    }
    $prev = $this->max;
    $this->max = $value;
    return $prev;
  }

  /**
   * @return int
   */
  public function getMin(){
    return $this->min;
  }

  /**
   * @return int
   */
  public function getMax(){
    return $this->max;
  }

  /**
   * @param int $min
   * @param int $max
   */
  public function setRange($min, $max){
    $this->setMin($min);
    $this->setMax($max);
  }

  function validateValue($value) {
    SimpleValidator::getInstance()->validate('number', $this->errorReporter);

    assert(!(is_null($this->min) && is_null($this->max)), 'min and max cannot both be null!');

    if(is_null($this->max)){
      $errorName = 'range_min';
      $result = $value >= $this->min;
    }else if(is_null($this->min)){
      $errorName = 'range_max';
      $result = $value <= $this->max;
    }else{
      $errorName = 'range_error';
      $result = ($value >= $this->min) && ($value <= $this->max);
    }

    if(!$result)
      $this->errorReporter->addErrors($errorName, array('min'=>$this->min, 'max'=>$this->max));

    return $result;
  }

}
