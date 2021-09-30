<?php

namespace nsfw\forms;


use nsfw\errors\iErrorReporter;
use nsfw\template\Template;

interface Form {

  /**
   * @return Template
   */
  function getTemplate();

  /**
   * @param Template $tpl
   */
  function setTemplate(Template $tpl);

  function setTemplateFile($file);

  /**
   * @param string $attr
   * @param string $value
   */
  function setAttribute($attr, $value);

  /**
   * @param string $attr
   * @return string
   */
  function getAttribute($attr);

  /**
   * @return array Form attributes
   */
  public function getAttributes();


  /**
   * @return iErrorReporter
   */
  public function getErrorReporter();

  /**
   * @param iErrorReporter $errorReporter
   */
  public function setErrorReporter(iErrorReporter $errorReporter);

  function addField(FormField $ff);

  /**
   * @param string $name
   * @param string $type
   * @param string $default
   * @param array|string|Validator $validators
   * @return FormField
   */
  function addNewField($name, $type, $default = '', $validators = null);

    /**
   * @param string $name
   * @return FormField
   */
  public function getField($name);

  /**
   * @return array
   */
  public function getFields();

  /**
   * @return bool
   */
  function isSubmitted();
  function processPost($validate = true);

}
