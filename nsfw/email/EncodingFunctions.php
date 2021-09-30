<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 04-02-18
 * Time: 12:15 PM
 */

namespace nsfw\email;


class EncodingFunctions {
  /**
   * The lower maximum line length allowed by RFC 2822 section 2.1.1.
   * This length does NOT include the line break
   * 76 means that lines will be 77 or 78 chars depending on whether
   * the line break format is LF or CRLF; both are valid.
   *
   * @var int
   */
  const STD_LINE_LENGTH = 76;

  /**
   * The maximum line length allowed by RFC 2822 section 2.1.1.
   *
   * @var int
   */
  const MAX_LINE_LENGTH = 998;

  protected $charset;

  protected $lengthSub = 0;

  public static $lineEnding = "\r\n";

  /**
   * EncodingFunctions constructor.
   *
   * @param $charset
   */
  public function __construct($charset = 'UTF-8') {
    $this->charset = $charset;
  }

  /**
   * @return string
   */
  public function getCharset() {
    return $this->charset;
  }

  /**
   * @param string $charset
   */
  public function setCharset($charset) {
    $this->charset = $charset;
  }

  /**
   * @return int
   */
  public function getLengthSub() {
    return $this->lengthSub;
  }

  /**
   * @param int $lengthSub
   */
  public function setLengthSub($lengthSub) {
    $this->lengthSub = $lengthSub;
  }

          /**
   * Does a string contain any 8-bit chars (in any charset)?
   *
   * @param string $text
   *
   * @return bool
   */
  public function has8bitChars($text) {
    return (bool) preg_match('/[\x80-\xFF]/', $text);
  }


  /**
   * Detect if a string contains a line longer than the maximum line length
   * allowed by RFC 2822 section 2.1.1.
   *
   * @param string $str
   *
   * @return bool
   */
  public static function hasLineLongerThanMax($str) {
    return (bool) preg_match('/^(.{' . (self::MAX_LINE_LENGTH + strlen(static::$lineEnding)) . ',})/m', $str);
  }


  /**
   * Check if a string contains multi-byte characters.
   *
   * @param string $str multi-byte text to wrap encode
   *
   * @param null   $charset
   *
   * @return bool
   */
  public function hasMultiBytes($str, $charset = null) {
    if(empty($charset))
      $charset = $this->charset;
    if(function_exists('mb_strlen')) {
      return strlen($str) > mb_strlen($str, $charset);
    }

    // Assume no multibytes (we can't handle without mbstring functions anyway)
    return false;
  }

  /**
   *
   * @param string $str
   *
   * @return bool Returns true if all characters are printable ascii
   */
  public function isPrintable($str) {
    return preg_match('/[ -~]+/u', $str) != 0;
  }

  /**
   * @param string      $header
   * @param string      $encoding
   * @param string|null $fromEncoding if empty assums that it's the same as $encoding
   *
   * @return string
   */
  public function encodeHeaderValueOld($header, $encoding = 'UTF-8', $fromEncoding = null) {
    if(!empty($fromEncoding) && strtoupper($fromEncoding) != strtoupper($encoding)) {
      $header = mb_convert_encoding($header, $encoding, $fromEncoding);
    }

    if(!$this->has8bitChars($header)) {

      $start = '=?' . $encoding . '?B?';
      $end = '?=';
      $encoded = '';
      $linebreak = static::$lineEnding;
      $mb_length = mb_strlen($header, $encoding);
      // Each line must have length <= 75, including $start and $end
      $length = 75 - strlen($start) - strlen($end);
      // Average multi-byte ratio
      $ratio = $mb_length / strlen($header);
      // Base64 has a 4:3 ratio
      $avgLength = floor($length * $ratio * .75);
      for($i = 0; $i < $mb_length; $i += $offset) {
        $lookBack = 0;
        do {
          $offset = $avgLength - $lookBack;
          $chunk = mb_substr($header, $i, $offset, $encoding);
          $chunk = base64_encode($chunk);
          ++$lookBack;
        } while(strlen($chunk) > $length);
        if($i != 0)
          $encoded .= $linebreak;
        $encoded .= $chunk;
      }
      // Chomp the last linefeed
      $header = $encoded;
    }
    return $header;
  }

  /**
   * Strip newlines to prevent header injection.
   *
   * @param string $str
   *
   * @return string
   */
  public function secureHeader($str) {
    return trim(str_replace(["\r", "\n"], '', $str));
  }

  /**
   * Word-wrap message.
   * For use with mailers that do not automatically perform wrapping
   * and for quoted-printable encoded messages.
   * Original written by philippe.
   *
   * @param string $message The message to wrap
   * @param int    $length  The line length to wrap to
   * @param bool   $qp_mode Whether to run in Quoted-Printable mode
   *
   * @return string
   */
  public function wrapText($message, $length, $qp_mode = false) {
    $lineEnding = EncodingFunctions::$lineEnding;
    if($qp_mode) {
      $softBreak = sprintf(' =%s', $lineEnding);
    }
    else {
      $softBreak = $lineEnding;
    }
    // If utf-8 encoding is used, we will need to make sure we don't
    // split multibyte characters when we wrap
    $is_utf8 = 'utf-8' == strtolower($this->charset);
    $lelen = strlen($lineEnding);
    $crlflen = strlen($lineEnding);
    $message = $this->normalizeBreaks($message);
    //Remove a trailing line break
    if(substr($message, -$lelen) == $lineEnding) {
      $message = substr($message, 0, -$lelen);
    }
    //Split message into lines
    $lines = explode($lineEnding, $message);
    //Message will be rebuilt in here
    $message = '';
    foreach($lines as $line) {
      $words = explode(' ', $line);
      $buf = '';
      $firstword = true;
      foreach($words as $word) {
        if($qp_mode and (strlen($word) > $length)) {
          $space_left = $length - strlen($buf) - $crlflen;
          if(!$firstword) {
            if($space_left > 20) {
              $len = $space_left;
              if($is_utf8) {
                $len = $this->utf8CharBoundary($word, $len);
              }
              elseif('=' == substr($word, $len - 1, 1)) {
                --$len;
              }
              elseif('=' == substr($word, $len - 2, 1)) {
                $len -= 2;
              }
              $part = substr($word, 0, $len);
              $word = substr($word, $len);
              $buf .= ' ' . $part;
              $message .= $buf . sprintf('=%s', $lineEnding);
            }
            else {
              $message .= $buf . $softBreak;
            }
            $buf = '';
          }
          while(strlen($word) > 0) {
            if($length <= 0) {
              break;
            }
            $len = $length;
            if($is_utf8) {
              $len = $this->utf8CharBoundary($word, $len);
            }
            elseif('=' == substr($word, $len - 1, 1)) {
              --$len;
            }
            elseif('=' == substr($word, $len - 2, 1)) {
              $len -= 2;
            }
            $part = substr($word, 0, $len);
            $word = substr($word, $len);
            if(strlen($word) > 0) {
              $message .= $part . sprintf('=%s', $lineEnding);
            }
            else {
              $buf = $part;
            }
          }
        }
        else {
          $buf_o = $buf;
          if(!$firstword) {
            $buf .= ' ';
          }
          $buf .= $word;
          if(strlen($buf) > $length and '' != $buf_o) {
            $message .= $buf_o . $softBreak;
            $buf = $word;
          }
        }
        $firstword = false;
      }
      $message .= $buf . $lineEnding;
    }

    return $message;
  }

  /**
   * Encode a header value (not including its label) optimally.
   * Picks shortest of Q, B, or none. Result includes folding if needed.
   * See RFC822 definitions for phrase, comment and text positions.
   *
   * @param string $str      The header value to encode
   * @param string $position What context the string will be used in
   *
   * @return string
   */
  public function encodeHeader($str, $position = 'text') {
    $lineEnding = self::$lineEnding;
    $matchCount = 0;
    switch(strtolower($position)) {
      case 'phrase':
        if(!preg_match('/[\200-\377]/', $str)) {
          // Can't use addslashes as we don't know the value of magic_quotes_sybase
          $encoded = addcslashes($str, "\0..\37\177\\\"");
          if(($str == $encoded) and !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
            return $encoded;
          }

          return '"$encoded"';
        }
        $matchCount = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
        break;
      /* @noinspection PhpMissingBreakStatementInspection */
      case 'comment':
        $matchCount = preg_match_all('/[()"]/', $str, $matches);
      //fallthrough
      case 'text':
      default:
        $matchCount += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
        break;
    }
    //RFCs specify a maximum line length of 78 chars, however mail() will sometimes
    //corrupt messages with headers longer than 65 chars. See #818
//    $lengthSub = 'mail' == $this->Mailer ? 13 : 0;
    $maxlen = static::STD_LINE_LENGTH - $this->lengthSub;
    // Try to select the encoding which should produce the shortest output
    if($matchCount > strlen($str) / 3) {
      // More than a third of the content will need encoding, so B encoding will be most efficient
      $encoding = 'B';
      //This calculation is:
      // max line length
      // - shorten to avoid mail() corruption
      // - Q/B encoding char overhead ("` =?<charset>?[QB]?<content>?=`")
      // - charset name length
      $maxlen = static::STD_LINE_LENGTH - $this->lengthSub - 8 - strlen($this->charset);
      if($this->hasMultiBytes($str)) {
        // Use a custom function which correctly encodes and wraps long
        // multibyte strings without breaking lines within a character
        $encoded = $this->base64EncodeWrapMB($str, "\n");
      }
      else {
        $encoded = base64_encode($str);
        $maxlen -= $maxlen % 4;
        $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
      }
      $encoded = preg_replace('/^(.*)$/m', ' =?' . $this->charset . "?$encoding?\\1?=", $encoded);
    }
    elseif($matchCount > 0) {
      //1 or more chars need encoding, use Q-encode
      $encoding = 'Q';
      //Recalc max line length for Q encoding - see comments on B encode
      $maxlen = static::STD_LINE_LENGTH - $this->lengthSub - 8 - strlen($this->charset);
      $encoded = $this->encodeQ($str, $position);
      $encoded = $this->wrapText($encoded, $maxlen, true);
      $encoded = str_replace('=' . $lineEnding, "\n", trim($encoded));
      $encoded = preg_replace('/^(.*)$/m', ' =?' . $this->charset . "?$encoding?\\1?=", $encoded);
    }
    elseif(strlen($str) > $maxlen) {
      //No chars need encoding, but line is too long, so fold it
      $encoded = trim($this->wrapText($str, $maxlen, false));
      if($str == $encoded) {
        //Wrapping nicely didn't work, wrap hard instead
        $encoded = trim(chunk_split($str, static::STD_LINE_LENGTH, $lineEnding));
      }
      $encoded = str_replace($lineEnding, "\n", trim($encoded));
      $encoded = preg_replace('/^(.*)$/m', ' \\1', $encoded);
    }
    else {
      //No reformatting needed
      return $str;
    }

    return trim($this->normalizeBreaks($encoded));
  }

  /**
   * Normalize line breaks in a string.
   * Converts UNIX LF, Mac CR and Windows CRLF line breaks into a single line break format.
   * Defaults to CRLF (for message bodies) and preserves consecutive breaks.
   *
   * @param string $text
   * @param string $lineEnding What kind of line break to use; defaults to static::$lineEnding
   *
   * @return string
   */
  public function normalizeBreaks($text, $lineEnding = null) {
    if(null === $lineEnding) {
      $lineEnding = static::$lineEnding;
    }
    // Normalise to \n
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    // Now convert LE as needed
    if("\n" !== $lineEnding) {
      $text = str_replace("\n", $lineEnding, $text);
    }

    return $text;
  }

  /**
   * Encode and wrap long multibyte strings for mail headers
   * without breaking lines within a character.
   * Adapted from a function by paravoid.
   *
   * @see http://www.php.net/manual/en/function.mb-encode-mimeheader.php#60283
   *
   * @param string $str       multi-byte text to wrap encode
   * @param string $linebreak string to use as linefeed/end-of-line
   *
   * @return string
   */
  public function base64EncodeWrapMB($str, $linebreak = null) {
    $start = '=?' . $this->charset . '?B?';
    $end = '?=';
    $encoded = '';
    if(null === $linebreak) {
      $linebreak = static::$lineEnding;
    }
    $mb_length = mb_strlen($str, $this->charset);
    // Each line must have length <= 75, including $start and $end
    $length = 75 - strlen($start) - strlen($end);
    // Average multi-byte ratio
    $ratio = $mb_length / strlen($str);
    // Base64 has a 4:3 ratio
    $avgLength = floor($length * $ratio * .75);
    for($i = 0; $i < $mb_length; $i += $offset) {
      $lookBack = 0;
      do {
        $offset = $avgLength - $lookBack;
        $chunk = mb_substr($str, $i, $offset, $this->charset);
        $chunk = base64_encode($chunk);
        ++$lookBack;
      } while(strlen($chunk) > $length);
      if($i != 0)
        $encoded .= $linebreak;
      $encoded .= $chunk ;
    }

    return $encoded;
  }

  /**
   * Encode a string in quoted-printable format.
   * According to RFC2045 section 6.7.
   *
   * @param string $string The text to encode
   *
   * @return string
   */
  public function encodeQP($string) {
    return $this->normalizeBreaks(quoted_printable_encode($string));
  }


  /**
   * Encode a string using Q encoding.
   *
   * @see http://tools.ietf.org/html/rfc2047#section-4.2
   *
   * @param string $str      the text to encode
   * @param string $position Where the text is going to be used, see the RFC for what that means
   *
   * @return string
   */
  public function encodeQ($str, $position = 'text') {
    // There should not be any EOL in the string
    $pattern = '';
    $encoded = str_replace(["\r", "\n"], '', $str);
    switch(strtolower($position)) {
      case 'phrase':
        // RFC 2047 section 5.3
        $pattern = '^A-Za-z0-9!*+\/ -';
        break;
      /*
       * RFC 2047 section 5.2.
       * Build $pattern without including delimiters and []
       */
      /* @noinspection PhpMissingBreakStatementInspection */
      case 'comment':
        $pattern = '\(\)"';
      /* Intentional fall through */
      case 'text':
      default:
        // RFC 2047 section 5.1
        // Replace every high ascii, control, =, ? and _ characters
        /** @noinspection SuspiciousAssignmentsInspection */
        $pattern = '\000-\011\013\014\016-\037\075\077\137\177-\377' . $pattern;
        break;
    }
    $matches = [];
    if(preg_match_all("/[{$pattern}]/", $encoded, $matches)) {
      // If the string contains an '=', make sure it's the first thing we replace
      // so as to avoid double-encoding
      $eqkey = array_search('=', $matches[0]);
      if(false !== $eqkey) {
        unset($matches[0][$eqkey]);
        array_unshift($matches[0], '=');
      }
      foreach(array_unique($matches[0]) as $char) {
        $encoded = str_replace($char, '=' . sprintf('%02X', ord($char)), $encoded);
      }
    }
    // Replace spaces with _ (more readable than =20)
    // RFC 2047 section 4.2(2)
    return str_replace(' ', '_', $encoded);
  }

  /**
   * Find the last character boundary prior to $maxLength in a utf-8
   * quoted-printable encoded string.
   * Original written by Colin Brown.
   *
   * @param string $encodedText utf-8 QP text
   * @param int    $maxLength   Find the last character boundary prior to this length
   *
   * @return int
   */
  public function utf8CharBoundary($encodedText, $maxLength) {
    $foundSplitPos = false;
    $lookBack = 3;
    while(!$foundSplitPos) {
      $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
      $encodedCharPos = strpos($lastChunk, '=');
      if(false !== $encodedCharPos) {
        // Found start of encoded character byte within $lookBack block.
        // Check the encoded byte value (the 2 chars after the '=')
        $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
        $dec = hexdec($hex);
        if($dec < 128) {
          // Single byte character.
          // If the encoded char was found at pos 0, it will fit
          // otherwise reduce maxLength to start of the encoded char
          if($encodedCharPos > 0) {
            $maxLength -= $lookBack - $encodedCharPos;
          }
          $foundSplitPos = true;
        }
        elseif($dec >= 192) {
          // First byte of a multi byte character
          // Reduce maxLength to split at start of character
          $maxLength -= $lookBack - $encodedCharPos;
          $foundSplitPos = true;
        }
        elseif($dec < 192) {
          // Middle byte of a multi byte character, look further back
          $lookBack += 3;
        }
      }
      else {
        // No encoded character found
        $foundSplitPos = true;
      }
    }

    return $maxLength;
  }

  /**
   * Encode a string in requested format.
   * Returns an empty string on failure.
   *
   * @param string $str      The text to encode
   * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable
   *
   * @return string
   * @throws \Exception
   */
  public function encodeString($str, $encoding = 'base64') {
    $encoded = '';
    switch(strtolower($encoding)) {
      case 'base64':
        $encoded = chunk_split(
          base64_encode($str),
          static::STD_LINE_LENGTH,
          static::$lineEnding
        );
        break;
      case '7bit':
      case '8bit':
        $encoded = static::normalizeBreaks($str);
        // Make sure it ends with a line break
        if(substr($encoded, -(strlen(static::$lineEnding))) != static::$lineEnding) {
          $encoded .= static::$lineEnding;
        }
        break;
      case 'binary':
        $encoded = $str;
        break;
      case 'quoted-printable':
        $encoded = $this->encodeQP($str);
        break;
      default:
        throw new \Exception('Unknown encoding: ' . $encoding);
        break;
    }

    return $encoded;
  }

  /**
   * Encode a file attachment in requested format.
   * Returns an empty string on failure.
   *
   * @param string $path     The full path to the file
   * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
   *
   *
   * @return string
   * @throws \Exception
   */
  public function encodeFile($path, $encoding = 'base64') {
    $fileError = 'File Error: Could not open file: ';
    if(!file_exists($path)) {
      throw new \Exception($fileError. $path, 1);
    }
    $fileBuffer = file_get_contents($path);
    if(false === $fileBuffer) {
      throw new \Exception($fileError . $path, 1);
    }
    $fileBuffer = $this->encodeString($fileBuffer, $encoding);

    return $fileBuffer;

  }

}
