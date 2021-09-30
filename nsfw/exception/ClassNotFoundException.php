<?php

namespace nsfw\exception;


use Exception;

class ClassNotFoundException extends Exception{
  private $className;
  /**
   * ClassNotFoundException constructor.
   * @param string $className
   * @param string $message
   * @param int $code
   * @param Exception|null $previous
   */
  public function __construct($className, $message = '', $code = 0, Exception $previous = null) {
    $this->className = $className;
    if(empty($message))
      $message = 'Class "'.$className.'" not found';
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return string
   */
  public function getClassName() {
    return $this->className;
  }

}
