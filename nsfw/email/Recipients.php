<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 04-02-18
 * Time: 12:04 PM
 */

namespace nsfw\email;


/**
 * Class Recipients
 *
 * IDN are not supported yet
 *
 * @package nsfw\email
 */
class Recipients {
  /**
   * The array of 'to' names and addresses.
   *
   * @var array
   */
  protected $to = [];
  /**
   * The array of 'cc' names and addresses.
   *
   * @var array
   */
  protected $cc = [];
  /**
   * The array of 'bcc' names and addresses.
   *
   * @var array
   */
  protected $bcc = [];

  /**
   * Recipients constructor.
   *
   */
  public function __construct() {
  }

  /**
   * @param        $email
   * @param string $name
   */
  public function addRecipient($email, $name = '') {
    $this->to[] = new Contact($email, $name);
  }

  /**
   * @param        $email
   * @param string $name
   */
  public function addCc($email, $name = '') {
    $this->cc[] = new Contact($email, $name);
  }

  /**
   * @param        $email
   * @param string $name
   */
  public function addBcc($email, $name = '') {
    $this->bcc[] = new Contact($email, $name);
  }

  /**
   * @return array
   */
  public function getTo() {
    return $this->to;
  }

  /**
   * @return array
   */
  public function getCc() {
    return $this->cc;
  }

  /**
   * @return array
   */
  public function getBcc() {
    return $this->bcc;
  }

}
