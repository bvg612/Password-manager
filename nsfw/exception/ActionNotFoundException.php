<?php

namespace nsfw\exception;


use Exception;

class ActionNotFoundException extends Exception{
  private $action;
  private $actionFile;

  public function __construct($action, $actionFile = '', $message = '', $code = 0, Exception $previous = null ) {
    $this->action = $action;
    $this->actionFile = $actionFile;

    if(empty($message)) {
      $message = 'Action "' . $action . '" not found.';
      if(!empty($actionFile)) {
        $message .= ' Action file: "'.$actionFile.'"';
      }
    }

    parent::__construct($message, $code, $previous);
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * @return string
   */
  public function getActionFile() {
    return $this->actionFile;
  }

}
