<?php

namespace nsfw\forms;

use nsfw\errors\iErrorReporter;

class WebForm extends AbstractForm {

  /** @var iErrorReporter */
  protected static $defaultErrorReporter;

  /**
   * WebForm constructor.
   */
  public function __construct() {
    parent::__construct();
    if(isset($_SERVER['REQUEST_METHOD']))
      if(strtolower($_SERVER['REQUEST_METHOD']) == 'post')
        $this->submitted = true;
    if(!empty(self::$defaultErrorReporter))
      $this->errorReporter = self::$defaultErrorReporter;
  }

  /**
   * @param iErrorReporter $defaultErrorReporter
   */
  public static function setDefaultErrorReporter(iErrorReporter $defaultErrorReporter) {
    self::$defaultErrorReporter = $defaultErrorReporter;
  }

}
