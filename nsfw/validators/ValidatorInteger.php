<?php

namespace nsfw\validators;


class ValidatorInteger extends AbstractValidator{
  function validateValue($value) {
    return strval(intval($value)) === strval($value);
    //return preg_match('/-?[0-9]+/', $value);
  }

}
