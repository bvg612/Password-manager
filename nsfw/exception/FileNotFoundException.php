<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 28-04-16
 * Time: 11:44 AM
 */

namespace nsfw\exception;


class FileNotFoundException extends \Exception{
  /** @var string */
  protected $missingFile;

  public function __construct($missingFile, $message = '', $code = 0, $previous = null) {
    $this->missingFile = $missingFile;
    if(empty($message)) {
      $message = 'File "'.$missingFile.'" not found';
    }
    parent::__construct($message, $code, $previous);
  }


  /**
   * @return string
   */
  public function getMissingFile() {
    return $this->missingFile;
  }

}
