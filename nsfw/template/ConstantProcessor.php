<?php
/**
 * User: npelov
 * Date: 31-05-17
 * Time: 5:49 PM
 */

namespace nsfw\template;


class ConstantProcessor implements VarProcessor {
  function processExistingVar(&$varName, $varValue) {
    return $varValue;
  }

  function processMissingVar(&$varName, Template $tpl) {
    if(defined($varName)) {
      $value = constant($varName);
      if(is_string($value) || is_int($value) || is_float($value))
        return $value;
    }
    return false;
  }

}
