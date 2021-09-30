<?php
/**
 * User: npelov
 * Date: 23-05-17
 * Time: 12:47 AM
 */

namespace nsfw\errors;


abstract class AbstractErrorReporter implements iErrorReporter {

  public function getClearErrors() {
    $errors = $this->getErrors();
    $this->clearErrors();
    return $errors;
  }

  function getClearInfoMessages() {
    $msgs = $this->getInfoMessages();
    $this->clearInfoMessages();
    return $msgs;
  }

}
