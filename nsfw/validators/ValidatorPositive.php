<?php

namespace nsfw\validators;


class ValidatorPositive extends AbstractValidator{
  function validateValue($value) {
    $result = is_numeric($value) && $value > 0;
    if(!$result)
      $this->errorReporter->addErrors('positive');
    return $result;
  }

}
