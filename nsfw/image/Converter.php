<?php
/**
 * User: npelov
 * Date: 24-09-17
 * Time: 1:07 PM
 */

namespace nsfw\image;


class Converter {
  protected $resizeType = null;
  protected $resizeWidth = null;
  protected $resizeHeight = null;

  /**
   * @param $width
   * @param $height
   * @param string $type One of:
   *   'keep': do not resize
   *   'stretch': exact size
   *   'fit': fit in rectangle,
   *   'crop': fill the rectangle and crop the rest
   */
  public function setSizeFixed($width, $height, $type = 'fit') {
    $this->resizeType = $type;
    $this->resizeWidth = $width;
    $this->resizeHeight = $height;
  }

  /**
   * @param $dest
   */
  public function convert($dest) {
  }
}
