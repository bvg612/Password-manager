<?php

namespace nsfw\validators;


use Exception;
use nsfw\errors\iErrorReporter;

class ValidatorLength extends ValidatorRange{

  public function __construct($min, $max, iErrorReporter $errorReporter = null) {
    parent::__construct($min, $max, $errorReporter);
  }

  /**
   * @param int $min
   * @param int $max
   */
  function setLength($min, $max){
    $this->setMin($min);
    $this->setMax($max);
  }

  function validateValue($value) {
    $length = strlen($value);

    if(($this->min<0) && ($this->max<0))
      throw new Exception('min and max cannot both be null!');

    if($this->max < 0){
      $errorName = 'length_min';
      $result = $length >= $this->min;
    }else if($this->min < 0){
      $errorName = 'length_max';
      $result = $length <= $this->max;
    }else{
      $errorName = 'length_range';
      $result = ($length >= $this->min) && ($length <= $this->max);
    }

    if(!$result)
      $this->errorReporter->addErrors($errorName, array('min_length'=>$this->min, 'max_length'=>$this->max));
    return $result;
  }

}
