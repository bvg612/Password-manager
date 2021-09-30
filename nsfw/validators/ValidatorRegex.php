<?php

namespace nsfw\validators;

class ValidatorRegex extends AbstractValidator{
  protected $regex;
  protected $matches;

  public function __construct($regex = null){
    parent::__construct();
    if(!is_null($regex))
      $this->setRegex($regex);
  }

  function setRegex($regex){
    assert(is_string($regex) || is_integer($regex));
    $this->regex = $regex;
  }

  public function validateValue($value){
    $result = preg_match($this->regex, $value, $this->matches) > 0;

    if(!$result)
      $this->errorReporter->addErrors('Invalid format for field '.$this->name);

    return $result;
  }

}
