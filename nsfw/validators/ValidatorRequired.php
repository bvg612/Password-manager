<?php

namespace nsfw\validators;

class ValidatorRequired extends AbstractValidator{

  public function validateValue($value){
    $result = false;
    // is it a file
    if(is_array($value) && isset($value['error'])) {
      if ($value['error'] == UPLOAD_ERR_OK) {
        $result = true;
      }
    }else {
      if(!is_array($value) && strval($value) === "0")
        $result = true;
      else
        $result = !empty($value);
    }

    if(!$result)
      $this->errorReporter->addErrors('required: '.$this->getFieldName());

    return $result;
  }

}
