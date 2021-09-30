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
class Message_w_logic {

  /** @var Recipients */
  protected $recipients;


  /** @var string */
  public $from;

  /** @var string */
  public $fromName = '';

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

  protected $ef;

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

  /**
   * Which method to use to send mail.
   * Options: "mail", "sendmail", or "smtp".
   *
   * @var string
   */
  public $Mailer = 'mail';

  /** @var string The message's MIME type. */
  protected $message_type = '';

  /** @var string Unique ID used for message ID and boundaries. */
  protected $uniqueid = '';

  /** @var array The array of MIME boundary strings. */
  protected $boundary = [];

  /**
   * The message encoding.
   * Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
   *
   * @var string
   */
//  public $encoding = '8bit';
  public $encoding = 'quoted-printable';

  /** @var string */
  public $contentType = 'text/plain';

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
  public $mailerHeaders = [
  ];


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
   * @var array The array of attachments.
   *
   * @see Message:::addAttachment
   */
  protected $attachment = [];

  /**
   * The S/MIME certificate file path.
   *
   * @var string
   */
  protected $sign_cert_file = '';

  /**
   * The S/MIME key file path.
   *
   * @var string
   */
  protected $sign_key_file = '';

  /**
   * Message constructor.
   *
   * @param bool $html
   */
  public function __construct($html = false) {
    $this->recipients = new Recipients();
    $this->ef = new EncodingFunctions($this->charset);
    $this->html = $html;
  }

  public function getHeaders() {
    $headers = [
      'Content-Type: multipart/alternative; boundary="089e08e59467069c5f056a1ec91f"'
    ];



    return $headers;
  }

  public function getHeadersAsString() {
    return implode(EncodingFunctions::$lineEnding, $this->getHeaders());
  }

  /**
   * @param string $charset
   */
  public function setCharset($charset) {
    $this->charset = $charset;
    $this->ef->setCharset($charset);
  }

  public function __isset($name) {
    static $magicFields = ['recipients'=>true, 'charset'=>true];
    return array_key_exists($name, $magicFields);
  }

  /**
   * @param string $name
   *
   * @return null|string
   * @throws \Exception
   */
  public function __get($name) {
    switch($name) {
      case 'recipients':
        return $this->recipients;
      case 'charset':
        return $this->charset;
    }
    throw new \Exception('Field '.$name.' does not exist');
  }

  /**
   * @param string $name
   * @param mixed $value
   *
   * @throws \Exception
   */
  public function __set($name, $value) {
    switch($name) {
      case 'recipients':
        throw new \Exception('Field '.$name.' is readonly');
      case 'charset':
        $this->setCharset($value);
    }
    throw new \Exception('Field '.$name.' does not exist');
  }

  /**
   * @return string
   */
  public function getSubjectEncoded() {
    return $this->ef->encodeHeader($this->subject);
  }


  /**
   * Map a file name to a MIME type.
   * Defaults to 'application/octet-stream', i.e.. arbitrary binary data.
   *
   * @param string $filename A file name or full path, does not need to exist as a file
   *
   * @return string
   */
  public static function filenameToType($filename) {
    // In case the path is a URL, strip any query string before getting extension
    $qpos = strpos($filename, '?');
    if(false !== $qpos) {
      $filename = substr($filename, 0, $qpos);
    }
    $ext = static::mb_pathinfo($filename, PATHINFO_EXTENSION);

    return static::_mime_types($ext);
  }

  /**
   * Add an attachment from a path on the filesystem.
   * Never use a user-supplied path to a file!
   * Returns false if the file could not be found or read.
   *
   * @param string $path        Path to the attachment
   * @param string $name        Overrides the attachment name
   * @param string $encoding    File encoding (see $Encoding)
   * @param string $type        File extension (MIME) type
   * @param string $disposition Disposition to use
   *
   * ToDo: make attachment an object
   *
   * @return bool
   * @throws \Exception
   */
  public function addAttachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment') {
    if(!@is_file($path)) {
      throw new \Exception('Could not access file: '. $path,  1);
    }
    // If a MIME type is not specified, try to work it out from the file name
    if('' == $type) {
      $type = static::filenameToType($path);
    }
    $filename = basename($path);
    if('' == $name) {
      $name = $filename;
    }
    $this->attachment[] = [
      0 => $path,
      1 => $filename,
      2 => $name,
      3 => $encoding,
      4 => $type,
      5 => false, // isStringAttachment
      6 => $disposition,
      7 => $name,
    ];

    return true;
  }

  /**
   * Create a unique ID to use for boundaries.
   *
   * @return string
   * @throws \Exception
   */
  protected function generateId() {
    $len = 32; //32 bytes = 256 bits
    if(function_exists('random_bytes')) {
      $bytes = random_bytes($len);
    }
    elseif(function_exists('openssl_random_pseudo_bytes')) {
      $bytes = openssl_random_pseudo_bytes($len);
    }
    else {
      //Use a hash to force the length to the same as the other methods
      $bytes = hash('sha256', uniqid((string) mt_rand(), true), true);
    }

    //We don't care about messing up base64 format here, just want a random string
    return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $bytes, true)));
  }

  /**
   * Return a formatted mail line.
   *
   * @param string $value
   *
   * @return string
   */
  public function textLine($value) {
    return $value . EncodingFunctions::$lineEnding;
  }

  /**
   * Format a header line.
   *
   * @param string     $name
   * @param string|int $value
   *
   * @return string
   */
  public function headerLine($name, $value) {
    return $name . ': ' . $value . EncodingFunctions::$lineEnding;
  }

  /**
   * Return the start of a message boundary.
   *
   * @param string $boundary
   * @param string $charSet
   * @param string $contentType
   * @param string $encoding
   *
   * @return string
   */
  protected function getBoundary($boundary, $charSet, $contentType, $encoding) {
    $result = '';
    if('' == $charSet) {
      $charSet = $this->charset;
    }
    if('' == $contentType) {
      $contentType = $this->contentType;
    }
    if('' == $encoding) {
      $encoding = $this->encoding;
    }
    $result .= $this->textLine('--' . $boundary);
    $result .= sprintf('Content-Type: %s; charset=%s', $contentType, $charSet);
    $result .= EncodingFunctions::$lineEnding;
    // RFC1341 part 5 says 7bit is assumed if not specified
    if('7bit' != $encoding) {
      $result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
    }
    $result .= EncodingFunctions::$lineEnding;

    return $result;
  }

  /**
   * Return the end of a message boundary.
   *
   * @param string $boundary
   *
   * @return string
   */
  protected function endBoundary($boundary) {
    return EncodingFunctions::$lineEnding . '--' . $boundary . '--' . EncodingFunctions::$lineEnding;
  }

  /**
   * Get the message MIME type headers.
   *
   * @return array
   */
  public function getMailMIME() {
    $result = [];
    $isMultipart = true;
    switch($this->message_type) {
      case 'inline':
        $result[] = $this->headerLine('Content-Type', 'multipart/related;');
        $result[] = $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
        break;
      case 'attach':
      case 'inline_attach':
      case 'alt_attach':
      case 'alt_inline_attach':
        $result[] = $this->headerLine('Content-Type', 'multipart/mixed;');
        $result[] = $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
        break;
      case 'alt':
      case 'alt_inline':
        $result[] = $this->headerLine('Content-Type', 'multipart/alternative;');
        $result[] = $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
        break;
      default:
        // Catches case 'plain': and case '':
        $result .= $this->textLine('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);
        $isMultipart = false;
        break;
    }
    // RFC1341 part 5 says 7bit is assumed if not specified
    if('7bit' != $this->encoding) {
      // RFC 2045 section 6.4 says multipart MIME parts may only use 7bit, 8bit or binary CTE
      if($isMultipart) {
        if('8bit' == $this->encoding) {
          $result[] = $this->headerLine('Content-Transfer-Encoding', '8bit');
        }
        // The only remaining alternatives are quoted-printable and base64, which are both 7bit compatible
      } else {
        $result[] = $this->headerLine('Content-Transfer-Encoding', $this->encoding);
      }
    }

    // ToDo: why is this needed
//    if('mail' != $this->Mailer) {
//      $result[] = EncodingFunctions::$lineEnding;
//    }

    return $result;
  }

  /**
   * Apply word wrapping to the message body.
   * Wraps the message body to the number of chars set in the WordWrap property.
   * You should only do this to plain-text bodies as wrapping HTML tags may break them.
   * This is called automatically by createBody(), so you don't need to call it yourself.
   */
  public function setWordWrap() {
    if($this->WordWrap < 1) {
      return;
    }
    switch($this->message_type) {
      case 'alt':
      case 'alt_inline':
      case 'alt_attach':
      case 'alt_inline_attach':
        $this->altBody = $this->ef->wrapText($this->altBody, $this->WordWrap);
        break;
      default:
        $this->body = $this->ef->wrapText($this->body, $this->WordWrap);
        break;
    }
  }

  /**
   * Attach all file, string, and binary attachments to the message.
   * Returns an empty string on failure.
   *
   * @param string $disposition_type
   * @param string $boundary
   *
   * @return string
   * @throws \Exception
   */
  protected function attachAll($disposition_type, $boundary) {
    $lineEnding = EncodingFunctions::$lineEnding;
    // Return text of body
    $mime = [];
    $cidUniq = [];
    $incl = [];
    // Add all attachments
    foreach($this->attachment as $attachment) {
      // Check if it is a valid disposition_filter
      if($attachment[6] == $disposition_type) {
        // Check for string attachment
        $string = '';
        $path = '';
        $bString = $attachment[5];
        if($bString) {
          $string = $attachment[0];
        } else {
          $path = $attachment[0];
        }
        $inclhash = hash('sha256', serialize($attachment));
        if(in_array($inclhash, $incl)) {
          continue;
        }
        $incl[] = $inclhash;
        $name = $attachment[2];
        $encoding = $attachment[3];
        $type = $attachment[4];
        $disposition = $attachment[6];
        $cid = $attachment[7];
        if('inline' == $disposition and array_key_exists($cid, $cidUniq)) {
          continue;
        }
        $cidUniq[$cid] = true;
        $mime[] = sprintf('--%s%s', $boundary, $lineEnding);
        //Only include a filename property if we have one
        if(!empty($name)) {
          $mime[] = sprintf(
            'Content-Type: %s; name="%s"%s',
            $type,
            $this->ef->encodeHeader($this->ef->secureHeader($name)),
            $lineEnding
          );
        } else {
          $mime[] = sprintf(
            'Content-Type: %s%s',
            $type,
            $lineEnding
          );
        }
        // RFC1341 part 5 says 7bit is assumed if not specified
        if('7bit' != $encoding) {
          $mime[] = sprintf('Content-Transfer-Encoding: %s%s', $encoding, $lineEnding);
        }
        if(!empty($cid)) {
          $mime[] = sprintf('Content-ID: <%s>%s', $cid, $lineEnding);
        }
        // If a filename contains any of these chars, it should be quoted,
        // but not otherwise: RFC2183 & RFC2045 5.1
        // Fixes a warning in IETF's msglint MIME checker
        // Allow for bypassing the Content-Disposition header totally
        if(!(empty($disposition))) {
          $encoded_name = $this->ef->encodeHeader($this->ef->secureHeader($name));
          if(preg_match('/[ \(\)<>@,;:\\"\/\[\]\?=]/', $encoded_name)) {
            $mime[] = sprintf(
              'Content-Disposition: %s; filename="%s"%s',
              $disposition,
              $encoded_name,
              $lineEnding . $lineEnding
            );
          } else {
            if(!empty($encoded_name)) {
              $mime[] = sprintf(
                'Content-Disposition: %s; filename=%s%s',
                $disposition,
                $encoded_name,
                $lineEnding . $lineEnding
              );
            } else {
              $mime[] = sprintf(
                'Content-Disposition: %s%s',
                $disposition,
                $lineEnding . $lineEnding
              );
            }
          }
        } else {
          $mime[] = $lineEnding;
        }
        // Encode as string attachment
        if($bString) {
          $mime[] = $this->ef->encodeString($string, $encoding);
        } else {
          $mime[] = $this->ef->encodeFile($path, $encoding);
        }
        $mime[] = $lineEnding;
      }
    }
    $mime[] = sprintf('--%s--%s', $boundary, $lineEnding);

    return implode('', $mime);
  }

  /**
   * Assemble the message body.
   * Returns an empty string on failure.
   *
   * @return string The assembled message body
   * @throws \Exception
   */
  public function getBody() {
    $body = '';
    //Create unique IDs and preset boundaries
    $this->uniqueid = $this->generateId();
    $this->boundary[1] = 'b1_' . $this->uniqueid;
    $this->boundary[2] = 'b2_' . $this->uniqueid;
    $this->boundary[3] = 'b3_' . $this->uniqueid;
    if($this->sign_key_file) {
      $body .= $this->getMailMIME() . EncodingFunctions::$lineEnding;
    }
    $this->setWordWrap();
    $bodyEncoding = $this->encoding;
    $bodyCharSet = $this->charset;
    //Can we do a 7-bit downgrade?
    if('8bit' == $bodyEncoding and !$this->ef->has8bitChars($this->body)) {
      $bodyEncoding = '7bit';
      //All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
      $bodyCharSet = 'us-ascii';
    }
    //If lines are too long, and we're not already using an encoding that will shorten them,
    //change to quoted-printable transfer encoding for the body part only
    if('base64' != $this->encoding and EncodingFunctions::hasLineLongerThanMax($this->body)) {
      $bodyEncoding = 'quoted-printable';
    }
    $altBodyEncoding = $this->encoding;
    $altBodyCharSet = $this->charset;
    //Can we do a 7-bit downgrade?
    if('8bit' == $altBodyEncoding and !$this->ef->has8bitChars($this->altBody)) {
      $altBodyEncoding = '7bit';
      //All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
      $altBodyCharSet = 'us-ascii';
    }
    //If lines are too long, and we're not already using an encoding that will shorten them,
    //change to quoted-printable transfer encoding for the alt body part only
    if('base64' != $altBodyEncoding and EncodingFunctions::hasLineLongerThanMax($this->altBody)) {
      $altBodyEncoding = 'quoted-printable';
    }
    //Use this as a preamble in all multipart message types
    $mimepre = 'This is a multi-part message in MIME format.' . EncodingFunctions::$lineEnding;
    switch($this->message_type) {
      case 'inline':
        $body .= $mimepre;
        $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('inline', $this->boundary[1]);
        break;
      case 'attach':
        $body .= $mimepre;
        $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, '', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('attachment', $this->boundary[1]);
        break;
      case 'inline_attach':
        $body .= $mimepre;
        $body .= $this->textLine('--' . $this->boundary[1]);
        $body .= $this->headerLine('Content-Type', 'multipart/related;');
        $body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, '', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('inline', $this->boundary[2]);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('attachment', $this->boundary[1]);
        break;
      case 'alt':
        $body .= $mimepre;
        $body .= $this->getBoundary($this->boundary[1], $altBodyCharSet, 'text/plain', $altBodyEncoding);
        $body .= $this->ef->encodeString($this->altBody, $altBodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, 'text/html', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        if(!empty($this->Ical)) {
          $body .= $this->getBoundary($this->boundary[1], '', 'text/calendar; method=REQUEST', '');
          $body .= $this->ef->encodeString($this->Ical, $this->encoding);
          $body .= EncodingFunctions::$lineEnding;
        }
        $body .= $this->endBoundary($this->boundary[1]);
        break;
      case 'alt_inline':
        $body .= $mimepre;
        $body .= $this->getBoundary($this->boundary[1], $altBodyCharSet, 'text/plain', $altBodyEncoding);
        $body .= $this->ef->encodeString($this->altBody, $altBodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->textLine('--' . $this->boundary[1]);
        $body .= $this->headerLine('Content-Type', 'multipart/related;');
        $body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, 'text/html', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('inline', $this->boundary[2]);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->endBoundary($this->boundary[1]);
        break;
      case 'alt_attach':
        $body .= $mimepre;
        $body .= $this->textLine('--' . $this->boundary[1]);
        $body .= $this->headerLine('Content-Type', 'multipart/alternative;');
        $body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[2], $altBodyCharSet, 'text/plain', $altBodyEncoding);
        $body .= $this->ef->encodeString($this->altBody, $altBodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, 'text/html', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        if(!empty($this->Ical)) {
          $body .= $this->getBoundary($this->boundary[2], '', 'text/calendar; method=REQUEST', '');
          $body .= $this->ef->encodeString($this->Ical, $this->encoding);
        }
        $body .= $this->endBoundary($this->boundary[2]);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('attachment', $this->boundary[1]);
        break;
      case 'alt_inline_attach':
        $body .= $mimepre;
        $body .= $this->textLine('--' . $this->boundary[1]);
        $body .= $this->headerLine('Content-Type', 'multipart/alternative;');
        $body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[2], $altBodyCharSet, 'text/plain', $altBodyEncoding);
        $body .= $this->ef->encodeString($this->altBody, $altBodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->textLine('--' . $this->boundary[2]);
        $body .= $this->headerLine('Content-Type', 'multipart/related;');
        $body .= $this->textLine("\tboundary=\"" . $this->boundary[3] . '"');
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->getBoundary($this->boundary[3], $bodyCharSet, 'text/html', $bodyEncoding);
        $body .= $this->ef->encodeString($this->body, $bodyEncoding);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('inline', $this->boundary[3]);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->endBoundary($this->boundary[2]);
        $body .= EncodingFunctions::$lineEnding;
        $body .= $this->attachAll('attachment', $this->boundary[1]);
        break;
      default:
        // Catch case 'plain' and case '', applies to simple `text/plain` and `text/html` body content types
        //Reset the `Encoding` property in case we changed it for line length reasons
        $this->encoding = $bodyEncoding;
        $body .= $this->ef->encodeString($this->body, $this->encoding);
        break;
    }
//    if($this->isError()) {
//      $body = '';
//      if($this->exceptions) {
//        throw new Exception($this->lang('empty_message'), self::STOP_CRITICAL);
//      }
//    }

    if($this->sign_key_file) {
      if(!defined('PKCS7_TEXT')) {
        throw new \Exception('Extension missing: openssl');
      }
      // @TODO would be nice to use php://temp streams here
      $file = tempnam(sys_get_temp_dir(), 'mail');
      if(false === file_put_contents($file, $body)) {
        throw new \Exception('Signing Error: Could not write temp file');
      }
      $signed = tempnam(sys_get_temp_dir(), 'signed');
      //Workaround for PHP bug https://bugs.php.net/bug.php?id=69197
      if(empty($this->sign_extracerts_file)) {
        $sign = @openssl_pkcs7_sign(
          $file,
          $signed,
          'file://' . realpath($this->sign_cert_file),
          ['file://' . realpath($this->sign_key_file), $this->sign_key_pass],
          []
        );
      } else {
        $sign = @openssl_pkcs7_sign(
          $file,
          $signed,
          'file://' . realpath($this->sign_cert_file),
          ['file://' . realpath($this->sign_key_file), $this->sign_key_pass],
          [],
          PKCS7_DETACHED,
          $this->sign_extracerts_file
        );
      }
      @unlink($file);
      if($sign) {
        $body = file_get_contents($signed);
        @unlink($signed);
        //The message returned by openssl contains both headers and body, so need to split them up
        $parts = explode("\n\n", $body, 2);
        $this->MIMEHeader .= $parts[0] . EncodingFunctions::$lineEnding . EncodingFunctions::$lineEnding;
        $body = $parts[1];
      } else {
        @unlink($signed);
        throw new \Exception('Signing Error: ' . openssl_error_string());
      }
    }

    return $body;
  }


}
