<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 4/19/2018
 * Time: 11:11 AM
 */

namespace nsfw\email;


class Attachment {
  /** @var string Full path of the file to attach */
  public $filename;

  /**
   * @var string Default is base64, one of
   * "8bit", "7bit", "binary", "base64", and "quoted-printable".
   */
  public $encoding = 'base64';

  /** @var string mime type */
  public $type = 'application/octet-stream';

}
