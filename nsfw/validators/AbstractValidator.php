<?php

namespace nsfw\validators;


use nsfw\errors\iErrorReporter;
use nsfw\errors\ErrorReporter;

abstract class AbstractValidator implements Validator{
  protected $name;
  /**
   * @var array
   */
  protected $vars = [];

  /** @var array Errors will be filled as ['lang_var'=>'language default error text'] */
  protected $errors = [];

  /** @var iErrorReporter */
  protected $errorReporter;

  /** @var bool */
  protected $translateErrors = false;

  /**
   * AbstractValidator constructor.
   * @param iErrorReporter $errorReporter
   */
  public function __construct(iErrorReporter $errorReporter = null) {
    if(empty($errorReporter))
      $errorReporter = new ErrorReporter(false);
    $this->errorReporter = $errorReporter;
    $this->vars = ['fieldName' => &$this->name];
  }

  /**
   * @return bool
   */
  public function isTranslateErrors() {
    return $this->translateErrors;
  }

  /**
   * @param bool $translateErrors
   */
  public function setTranslateErrors($translateErrors) {
    $this->translateErrors = $translateErrors;
  }



  /**
   * @return mixed
   */
  public function getFieldName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setFieldName($name) {
    $this->name = $name;
  }

  /**
   * @return iErrorReporter
   */
  public function getErrorReporter() {
    return $this->errorReporter;
  }

  /**
   * @param iErrorReporter $errorReporter
   */
  public function setErrorReporter(iErrorReporter $errorReporter = null) {
    $oldReporter = $this->errorReporter;
    $this->errorReporter = $errorReporter;
    if(!empty($oldReporter))
      $this->errorReporter->addErrors($oldReporter);
  }

  public function replaceVar($matches){
    $varName = $matches[1];

    $value = '';

    if(!empty($this->vars[$varName]))
      $value = $this->vars[$varName];

    return $value;
  }

  /**
   * @param string $errorIndex Error string with substitution vars. Ex: "Error in field {%fieldName}"
   */
  public function addError($errorIndex) {
    $this->vars['fieldName'] = $this->getFieldName();
    if($this->translateErrors)
      $error = $this->errors[$errorIndex]; // ToDo: Translate errors
    else
      $error = $this->errors[$errorIndex];

    $error = preg_replace_callback('/\\{%([a-zA-Z0-9_-]+)\\}/', [$this, 'replaceVar'], $error);
    $this->errorReporter->addErrors($error);
  }

}
