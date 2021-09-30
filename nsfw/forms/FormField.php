<?php

namespace nsfw\forms;


use nsfw\errors\iErrorReporter;
use nsfw\template\DisplayObject;
use nsfw\validators\Validator;

/**
 * Interface FormField
 * @package nsfw\forms
 *
 * @property string $value
 * @property string $name
 * @property string $label
 */
interface FormField extends DisplayObject{

  public function getName();
  public function getType();

  public function getValue();
  public function setValue($value);

  public function setLabel($label);
  public function getLabel();

  /**
   * @param string $attr
   * @return string
   */
  public function getAttribute($attr);

  /**
   * @param string $attr
   * @param string $value
   */
  public function setAttribute($attr, $value);

  /**
   * @return bool
   */
  public function isRequired();
  public function setRequired($required);

  /**
   * @return iErrorReporter
   */
  public function getErrorReporter();

  /**
   * @param iErrorReporter $errorReporter
   */
  public function setErrorReporter(iErrorReporter $errorReporter);

  /**
   * Sets
   * @param string $order A string containing one or more of letters G, P, C and S, corresponding to GET, POST, COOKIE
   *    and SESSION respectively
   */
  public function setParamOrder($order);

  /**
   * @return string the parameter order
   * @see FormField::setParamOrder();
   */
  public function getParamOrder();

  /**
   * @param string|Validator $validator
   */
  public function addValidator($validator);

  /**
   * @param string|Validator $validator
   */
  public function insertValidator($validator);
  public function getValidators();

  public function validate();

  public function getFromPost();

  /**
   * @param string $newClass
   */
  public function addClass($newClass);

  /**
   * @param string $delClass
   */
  public function removeClass($delClass);

  public function isEmpty();

  /**
   * @return string
   */
  public function getLabelHtml();

}
