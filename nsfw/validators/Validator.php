<?php

namespace nsfw\validators;


use nsfw\errors\iErrorReporter;

interface Validator {
  /**
   * @param $value
   * @return bool true if valid, false otherwise
   */
  function validateValue($value);

  function setFieldName($name);

  /**
   * Sets the error reporter.
   *
   * This method should preserve errors (copy them from old reporter) if there are any!
   *
   * @param iErrorReporter $errorReporter
   */
  function setErrorReporter(iErrorReporter $errorReporter = null);

  /**
   * @return iErrorReporter
   */
  function getErrorReporter();
}
