<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 4/18/2018
 * Time: 5:51 PM
 */

namespace nsfw\email;


class Contact {
  public $email;
  public $name;

  /**
   * EmailContact constructor.
   * @param $email
   * @param $name
   */
  public function __construct($email, $name = '') {
    $this->email = $email;
    $this->name = $name;
  }

}
