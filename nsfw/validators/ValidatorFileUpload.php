<?php
/**
 * User: npelov
 * Date: 16-06-17
 * Time: 11:42 AM
 */

namespace nsfw\validators;


use nsfw\errors\ErrorReporter;

class ValidatorFileUpload extends AbstractValidator {
  protected static $defaultErrors = [
    'invalid_upload' =>'Error uploading file for {%fieldName}',
    'file_too_big' =>'File is too big.',
    'unknown_file_upload_error' =>'Unknown error occurred while uploading file.',
    'invalid_file_type' =>'Invalid file type: {%invalidType}. Allowed file types: {%allowedTypes}',
  ];

  protected $allowedExtensions = [];

  /**
   * ValidatorFileUpload constructor.
   * @param iErrorReporter|null $errorReporter
   * @internal param ErrorReporter $er
   */
  public function __construct(iErrorReporter $errorReporter = null) {
    parent::__construct($errorReporter);
    $this->errors = self::$defaultErrors;
  }

  public static function setDefaultErrors(array $errors) {
    self::$defaultErrors = $errors;
  }

  /**
   * @param string $errorIndex
   * @param string $errorText
   */
  public function setErrorText($errorIndex, $errorText) {
    $this->errors[$errorIndex] = $errorText;
  }

  /**
   * @return array
   */
  public function getAllowedExtensions() {
    return $this->allowedExtensions;
  }

  /**
   * @param array|string $allowedExtensions Allowed extensions as array or comma separated string
   */
  public function setAllowedExtensions($allowedExtensions) {
    if(is_array($allowedExtensions)) {
      $exts = array_combine($allowedExtensions, $allowedExtensions);
    } else if(is_string($allowedExtensions)) {
      $exts = explode(',', $allowedExtensions);
    }else{
      return;
    }
    $this->allowedExtensions = [];
    foreach($exts as $ext) {
      $ext = trim($ext);
      if(empty($ext))
        continue;
      if($ext == '*') {
        $this->allowedExtensions = '*';
        break;
      }
      $this->allowedExtensions[$ext] = $ext;
    }
  }



  protected function checkKeys(array $keys, $value) {
    if(!is_array($value))
      return false;
    foreach($keys as $key) {
      if(!array_key_exists($key, $value))
        return false;
    }
    return true;
  }

  public function validateValue($value) {
    if(!$this->checkKeys(['error', 'name', 'type', 'size', 'tmp_name'], $value)) {
      // we don't care about empty file
      //$this->addError('invalid_upload');
      return true;
    }
    if(empty($value['tmp_name'])) {
      // no file uploaded - that's for "required" validator
      return true;
    }
    switch($value['error']) {
      case UPLOAD_ERR_OK:
        break;
      case UPLOAD_ERR_NO_FILE:
        return true;
//        $this->addError('file_not_uploaded');
//        return false;
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        $this->addError('file_too_big');
        return false;
      default:
        $this->addError('file_too_big');
        return false;

    }
    if(!is_uploaded_file($value['tmp_name'])) {
      $this->addError('invalid_upload');
      return false;
    }

    if(!empty($this->allowedExtensions) && reset($this->allowedExtensions) != '*') {
      $path = $value['name'];
      $ext = pathinfo($path, PATHINFO_EXTENSION);
      $this->vars['invalidType'] = $ext;
      $this->vars['allowedTypes'] = implode(',', $this->allowedExtensions);
      if(!array_key_exists($ext, $this->allowedExtensions)) {
        $this->addError('invalid_file_type');
        return false;
      }
    }

    return true;
  }

}
