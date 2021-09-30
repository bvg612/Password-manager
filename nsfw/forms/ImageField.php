<?php
/**
 * User: npelov
 * Date: 12-06-17
 * Time: 9:19 PM
 */

namespace nsfw\forms;


use nsfw\errors\ErrorReporter;
use nsfw\template\CascadedTemplate;

/**
 * Class ImageField
 * @package nsfw\forms
 *
 * @property array $value
 *
 */
class ImageField extends AbstractFormField {
  /** @var array $value */

  protected static $tpl = '
  <div class="imgField">
    <img src="{%imgSrc}" id="{%imgId}" width="{%width}" height="{%height}" /> <br/>
    <input type="file" name="{%name}" value=""{%attr} />
  </div>
  ';

  /** @var int Bounding width*/
  protected $tnWidth = 100;
  /** @var int Bounding height*/
  protected $tnHeight = 100;
  protected $tobj;
  protected $imgUrl = '/img/pixel.gif';
  protected $imgFile = '';

  /**
   * ImageField constructor.
   * @param $name
   * @param string|null $value
   * @param ErrorReporter|null $er
   */
  public function __construct($name, $value = null, ErrorReporter $er = null) {
    parent::__construct($name, $value, $er);
  }

  public function getFromPost() {
    if(isset($_FILES[$this->name])) {
      $this->value = $_FILES[$this->name];
    }
    //$file = $value = getParam($this->name, $this->default, strToUpper($this->paramOrder));
  }

  public function getFile() {
    if(isset($this->value['tmp_name']))
      return $this->value['tmp_name'];
    return false;
  }

  /**
   * @param string $dest
   * @return bool
   */
  public function moveFile($dest) {
    if(!isset($this->value['tmp_name']))
      return false;
    if(!is_uploaded_file($this->value['tmp_name']))
      return false;
    return move_uploaded_file($this->value['tmp_name'], $dest);
  }

  public function getType() {
    return 'image';
  }

  /**
   * @param int $width
   * @param int $height
   */
  public function setThumbSize($width, $height) {
    $this->tnWidth = $width;
    $this->tnHeight = $height;
  }

  public function setImage($imgFile, $imgUrl = '') {
    $this->imgFile = $imgFile;
    if(!empty($imgUrl))
      $this->imgUrl = $imgUrl;
  }

  private function getTemplateObject() {
    if(empty($this->tobj)){
      $this->tobj = new CascadedTemplate(static::$tpl);
      //$this->tobj->setTemplate(static::$tpl);
    }
    return $this->tobj;
  }

  protected function getImageDimentions($imgFile) {
    if(empty($imgFile)) {
      return [$this->tnWidth, $this->tnHeight];
    }
    $tnRatio = $this->tnWidth/$this->tnHeight;
    $imgSize = getimagesize($this->imgFile);
    $w = $imgSize[0];
    $h = $imgSize[1];
    $ratio = $w/$h;
    if($ratio>=$tnRatio) {
      $w = $this->tnWidth;
      $h = $w*$tnRatio;
    }else {
      $h = $this->tnHeight;
      $w = $h*$tnRatio;
    }
    return [$w, $h];
  }

  protected function prepareTemplate() {
    $tpl = $this->getTemplateObject();
    list($w, $h) = $this->getImageDimentions($this->imgFile);
    $tpl->setVars([
      'name' => $this->name,
      'width' => $w,
      'height' => $h,
      'imgSrc' => $this->imgUrl,
      'attr' => $this->getAttributesHtml(),
      'imgId' => $this->name.'_img',
    ]);
    return $tpl;
  }

  function getHtml() {
    $tpl = $this->prepareTemplate();
    return $tpl->getHtml();
  }

  /*
  public function validate() {
    $parentResult =  parent::validate();
    if(!$parentResult)
      return false;
    if(!isset($this->value['error']))
      return false;
    if($this->value['error'] != UPLOAD_ERR_OK) {
      // TODO: Check for image upload errors
      // ToDo: Copy/paste code from here: http://php.net/manual/en/features.file-upload.php
      $this->errorReporter->addErrors('Error uploading file');
    }
    return true;
  }
    */

  public function isEmpty() {
    if(!isset($this->value['tmp_name']))
      return true;
    if(!isset($this->value['error']))
      return true;
    if($this->value['error'] != UPLOAD_ERR_OK)
      return true;
    if(empty($this->value['name']) || empty($this->value['tmp_name']))
      return true;

    return false;
  }


}
