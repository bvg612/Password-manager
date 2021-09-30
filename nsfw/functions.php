<?php
namespace nsfw {
  function getPublicMembers($obj) {
    return get_object_vars($obj);
  }
}
////use Exception;

namespace {

  use nsfw\exception\FileNotFoundException;
  use nsfw\i18\CyrillicTools;

  /**
   * @param string $file
   * @param array $context
   * @throws FileNotFoundException
   */
  function safeInclude($file, array &$context = []) {
    if(!empty($context)) {
      foreach($context as $var => $value) {
        $$var = $value;
      }
    }

    if(!is_file($file))
      throw new FileNotFoundException($file);

    require $file;
  }

  function isDev() {
    if(defined('ENV')) {
      return ENV == 'dev';
    }
    return false;
  }

  function isProduction() {
    if(defined('ENV')) {
      return ENV == 'production';
    }
    return true;
  }

  /**
   * @param string $url
   * @param int $httpCode Optional HTTP code. Default 302
   * @codeCoverageIgnore
   */
  function httpRedirect($url, $httpCode = 302) {
    if(defined('DEBUG_REDIRECT') && constant('DEBUG_REDIRECT')) {
      echo "debug: redirect to \"$url\"";
      $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
      /** @var  $bt0 */
      $bt0 = $bt[0];
      $bt1 = $bt[1];
      $func = $bt1['function'] . '()';
      if(array_key_exists('object', $bt1))
        $func = get_class($bt1['object']) . '::' . $func;
      echo "called in $func at {$bt0['file']}:{$bt0['line']}";
      exit;
    }
    switch($httpCode) {
      case 301:
        header("HTTP/1.1 301 Moved Permanently");
        break;
      case 302:
        header("HTTP/1.1 302 Found");
        break;
    }

    header('Location: ' . $url);
    exit();
  }

  /**
   * Issues "not found" header and stops the script
   */
  function notFound() {
    header("HTTP/1.0 404 Not Found");
    echo "<html><head><title>404 Not Found</title></head><body>Page not found</body></html>";
    exit;
  }

  /**
   * @param string $arg
   * @param bool $default
   * @param string $order One or more of letters PGSC, which mean Post, Get, Session, Cookie respectively. Default: PGS
   * @return mixed|null
   *
   * @noinspection PhpUnusedLocalVariableInspection
   */
  function getParam($arg, $default = false, $order = 'PGS') {
    global $_GET, $_POST, $_COOKIE, $_SESSION, $_FILES;

    assert(ini_get('magic_quotes') == false);

    $P =& $_POST;
    $G =& $_GET;
    $C =& $_COOKIE;
    $S =& $_SESSION;
    $F =& $_FILES;
    for($i = 0; $i < strlen($order); $i++) {
      $V = strToUpper($order{$i});
      $var =& $$V;
      if($var == NULL) $var = Array();
      if(!isset($var)) $var = Array();
      if(array_key_exists($arg, $var)) {
        if(is_null($var[$arg]))
          return NULL;
        return $var[$arg];
      }
    }
    return $default;
  }

  /**
   * Converts ip to hexadecimal. 8 hex digits, 4 bytes, no separator
   *
   * @param string $ip
   * @return string
   *
   * @see hex2ip()
   */
  function ip2hex($ip) {
    return sprintf("%08X", ip2long($ip));
  }

  /**
   * @param string $iphex
   * @return string Ip in dot format
   *
   * @see ip2hex()
   */
  function hex2ip($iphex) {
    return long2ip('0x' . $iphex);
  }

  /**
   * A good way to test stiff and (quietly) log possible problems. In order the script to continue the error code must be
   * one of: E_USER_WARNING or E_USER_NOTICE.
   *
   * E_USER_ERROR stop wtih fatal error
   * E_RECOVERABLE_ERROR throws an exception
   *
   * @param bool $bool
   * @param string $msg
   * @param int $errorCode
   * @return bool
   * @throws Exception
   */
  function test($bool, $msg = '', $errorCode = E_USER_WARNING) {
    if($bool)
      return true;

    $bt = debug_backtrace();
    $bt0 = $bt[0];
    $file = isset($bt0['file']) ? $bt0['file'] : '';
    $line = isset($bt0['line']) ? $bt0['line'] : '';
    $testLocation = ' in file ' . $file . ' on line ' . $line;
    if(!$msg)
      $msg = 'test failed ';
    unset($bt);
    if($errorCode == E_RECOVERABLE_ERROR) {
      throw new Exception($msg . $testLocation, $errorCode);
    } else {
      trigger_error($msg . $testLocation, $errorCode);
      return false;
    }
  }

  function titleToUrl($title) {
    $latStr = CyrillicTools::cyrToLat(trim($title));
    $replaced = preg_replace('/[^a-zA-Z0-9_-]+/i', '-', $latStr);
    $replaced = preg_replace('/[-]+/', '-', $replaced);
    return strtolower(trim($replaced, '-'));
  }

}
