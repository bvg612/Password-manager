<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 04-02-18
 * Time: 11:45 AM
 */

namespace nsfw\email;


/**
 * Class Message
 *
 *
 * ToDo: check what MIMEHeader property does and add it
 *
 * @property Recipients $recipients
 *
 * @package nsfw\email
 */
class Message {

  /** @var Recipients */
  public $recipients;

  /** @var Contact */
  public $replyTo;

  /** @var Contact */
  public $from;

  /**
   * The envelope sender of the message.
   * This will usually be turned into a Return-Path header by the receiver,
   * and is the address that bounces will be sent to.
   * If not empty, will be passed via `-f` to sendmail or as the 'MAIL FROM' value over SMTP.
   *
   * @var string
   */
  public $sender;

  /** @var string */
  public $subject = '';

  /**
   * An ID to be used in the Message-ID header.
   * If empty, a unique id will be generated.
   * You can set your own, but it must be in the format "<id@domain>",
   * as defined in RFC5322 section 3.6.4 or it will be ignored.
   *
   * @see https://tools.ietf.org/html/rfc5322#section-3.6.4
   *
   * @var string
   */
  public $messageId;

  /** @var string date('r'); */
  public $date;

  /** @var string  */
  protected $charset = 'UTF-8';

  /** @var string The message's MIME type. */
  protected $message_type = '';

  public $html = false;

  /** @var string */
  public $body = '';

  /**
   * The plain-text message body.
   * This body can be read by mail clients that do not have HTML email
   * capability such as mutt & Eudora.
   * Clients that can read HTML will view the normal Body.
   *
   * @var string
   */
  public $altBody = '';

  /** @var array Custom headers */
  public $customHeaders = [];

  /** @var array Do not use this propery. It's used by the mailer. Add all headers to $customHeaders. */
  public $mailerHeaders = [];

  /** @var array */
  public $attachments = [];


  /**
   * Word-wrap the message body to this number of chars.
   * Set to 0 to not wrap. A useful value here is 78, for RFC2822 section 2.1.1 compliance.
   *
   * @see EncodingFunctions::STD_LINE_LENGTH
   *
   * @var int
   */
  public $WordWrap = 0;

  /**
   * The S/MIME certificate file path.
   *
   * @var string
   */
  public $sign_cert_file = '';

  /**
   * The S/MIME key file path.
   *
   * @var string
   */
  public $sign_key_file = '';

  /**
   * Message constructor.
   *
   * @param bool $html
   */
  public function __construct($html = false) {
    $this->recipients = new Recipients();
    $this->html = $html;
  }

  public function addRecipient($email, $name = '') {
    $this->recipients = new Contact($email, $name);
  }

  /**
   * @param string $charset
   */
  public function setCharset($charset) {
    $this->charset = $charset;
  }






}
