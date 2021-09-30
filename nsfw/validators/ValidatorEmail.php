<?php

namespace nsfw\validators;


class ValidatorEmail extends AbstractValidator{
  function validateValue($value) {
    $emailMatch = '/^([a-z0-9\\._-]+)@([a-z0-9\\.-]+)\\.[a-z]{2,6}$/i';
    $result = preg_match($emailMatch, $value) > 0;
    if(!$result)
      $this->errorReporter->addErrors('email');
    return $result;
  }

}
