<?php

namespace nsfw\validators;


class ValidatorNumber extends AbstractValidator{
  function validateValue($value) {
    $result = is_numeric($value);
    if(!$result)
      $this->errorReporter->addErrors('number');
    return $result;

  }

}
