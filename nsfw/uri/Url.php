<?php
/**
 * User: npelov
 * Date: 05-07-17
 * Time: 3:52 PM
 */

namespace nsfw\uri;


use nsfw\template\DisplayObject;

class Url implements DisplayObject {
  // in order to allow full url at least following two variables must be set
  /** @var null|string */
  protected $scheme = null;
  /** @var null|string */
  protected $host = null;
  /** @var null|int */
  protected $port = null;

  /** @var string */
  protected $user;
  /** @var string */
  protected $pass;
  /** @var array */
  protected $params = [];

  /** @var string The path must include leading slash (/)*/
  protected $path = '';

  /** @var string */
  protected $basename;
  /** @var string|null */
  protected $fragment;

  public function __construct($url = null){
    if(is_null($url)){
      $host = $_SERVER['HTTP_HOST'];

      if(isset($_SERVER['REDIRECT_URL']))
        $url = $_SERVER['REDIRECT_URL'];
      else if(isset($_SERVER['PHP_SELF']))
        $url = $_SERVER['PHP_SELF'];
      else $url = '';
      if(isset($_SERVER['REDIRECT_QUERY_STRING']))
        $queryString = $_SERVER['REDIRECT_QUERY_STRING'];
      else
        $queryString = $_SERVER['QUERY_STRING'];
    }
    $this->setUrl($url);
  }

  public function setUrl($url){
    $parsed = parse_url($url);
    if(empty($parsed['scheme']))
      $parsed['scheme'] = null;

    if(empty($parsed['host']))
      $parsed['host'] = null;

    if(empty($parsed['port']))
      $parsed['port'] = null;

    if(empty($parsed['user']))
      $parsed['user'] = null;

    if(empty($parsed['pass']))
      $parsed['pass'] = null;

    if(empty($parsed['path']))
      $parsed['path'] = '';

    if(empty($parsed['query']))
      $parsed['query'] = null;

    if(empty($parsed['fragment']))
      $parsed['fragment'] = null;

    if($url[strlen($parsed['path'])-1] == '/'){
      $this->basename = '';
      $this->path = $parsed['path'];
    } else {
      $this->path = dirname($parsed['path']).'/';
      if ($this->path = '//')
        $this->path = '/';
      $this->basename = basename($parsed['path']);
    }

    $this->scheme = $parsed['scheme'];
    $this->host = $parsed['host'];
    $this->port = $parsed['port'];
    $this->user = $parsed['user'];
    $this->pass = $parsed['pass'];
    $this->clearParams();
    $this->import('V', $parsed['query']);
    $this->fragment = $parsed['fragment'];
  }

  /**
   * Returns url without query string or fragment
   * @return string
   */
  public function getUrl(){
    $url = $this->path.$this->basename;
    $server = '';
    if(!empty($this->host) && !empty($this->scheme)) {
      $server = $this->scheme."://";
      if(!empty($this->user)) {
        $server .= $this->user;
        if(!empty($this->pass))
          $server .= ':'.$this->pass;
        $server .= '@';
      }
      $server .= $this->host;
      if(!empty($this->port))
        $server .= ':'.$this->port;
      $url = $server.$url;
    }

    return $url;
  }

  /**
   * @param string $basename
   * @param array $removeIndex
   */
  public function setBasename($basename, $removeIndex = ['index.php', 'index.html'] ){
    if(in_array($basename, $removeIndex)) {
      if($basename == 'index.php' || $basename == 'index.html')
        $basename = '';
    }
    $this->basename = $basename;
  }

  public function getBasename(){
    return $this->basename;
  }

  /**
   * @return null|string
   */
  public function getFragment() {
    return $this->fragment;
  }

  /**
   * @param null|string $fragment
   */
  public function setFragment($fragment) {
    $this->fragment = $fragment;
  }

  /**
   * @return int|null
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @param int|null $port
   */
  public function setPort($port) {
    $this->port = $port;
  }

  /**
   * @return null|string
   */
  public function getScheme() {
    return $this->scheme;
  }

  /**
   * @param null|string $scheme
   */
  public function setScheme($scheme) {
    $this->scheme = $scheme;
  }

  /**
   * @return null|string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * @param null|string $host
   */
  public function setHost($host) {
    $this->host = $host;
  }

  /**
   * @return string
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * @param string $user
   */
  public function setUser($user) {
    $this->user = $user;
  }

  /**
   * @return string
   */
  public function getPass() {
    return $this->pass;
  }

  /**
   * @param string $pass
   */
  public function setPass($pass) {
    $this->pass = $pass;
  }

  public function setPath($path){
    $this->path = $path;
    $this->buildUrl();
  }

  /**
   * @return string Path including leading /
   */
  public function getPath(){
    return $this->path;
  }

  public function setParam($param, $value){
    $this->params[$param] = $value;
  }

  public function getParam($param, $default = null){
    if(array_key_exists($param, $this->params))
      return $this->params[$param];
    return $default;
  }

  function removeParam($param){
    if(array_key_exists($param, $this->params))
      unset($this->params[$param]);
  }
  function clearParams() {
    $this->params = [];
  }

  /**
   * @param array $params Additional params to merge together with the internal ones
   * @param bool $dontIncludeInternal
   * @return string
   */
  public function getQueryStr($params = [], $dontIncludeInternal = false) {
    if(!$dontIncludeInternal)
      $params = $this->mergeParams($params);
    $queryParams = [];
    foreach($params as $name=>$value) {
      if(is_array($value)) {
        foreach($value as $k => $v) {
          $queryParams[] = urlencode($name . '[' . $k . ']') . '=' . urlencode($v);
        }
      } else {
        $queryParams[] = urlencode($name) . '=' . urlencode($value);
      }
    }
    return implode('&', $queryParams);
  }

  /**
   * Imports parameters from get, post, session, cookies and second function parameter
   * @param string $order Can be one or more of following letters:
   * P - _POST
   * G - _GET
   * S - _SESSION
   * C - _COOKIE
   * V - $vars array or string in parameters
   * @param string|array $vars
   */
  function import($order = 'P', $vars = []){
    //global $_GET,$_POST,$_COOKIE,$_SESSION; // we don't need these. They are already globals

    if(is_string($vars)) {
      $varsInput = $vars;
      parse_str ( $varsInput, $vars);
    }

    if(isset($_POST))
      $P = &$_POST;
    else
      $P = array();
    if(isset($_GET))
      $G = &$_GET;
    else
      $G = array();
    if(isset($_COOKIE))
      $C = &$_COOKIE;
    else
      $C = array();

    if(isset($_SESSION))
      $S = &$_SESSION;
    else
      $S = array();

    $V = $vars;

    for($i=0;$i<strlen($order);$i++) {
      $varName=strToUpper($order{$i});
      $var=&$$varName;
      if(!is_array($var)){
        unset($var);
        $var = Array();
      }
      foreach($var as $arg=>$value){
        if(is_null($value))
          continue;
       $this->params[$arg] = $this->stripSlashesGpc($value);
      }
    }
  }

  /**
   * Returns unescaped string regardless of magic quotes state
   * @param mixed $value
   * @return string|mixed
   */
  protected function stripSlashesGpc($value) {
    if(!is_string($value))
      return $value;

    if(get_magic_quotes_gpc()) {
      return stripslashes($value);
    }else {
      return $value;
    }

  }

  /**
   * Merges parameters from $vars with internal params and returns the result. Does not change internal params
   * @param array $vars
   * @return array
   */
  public function mergeParams($vars){
    $params = $this->params;
    if(count($vars) > 0){
      foreach($vars as $key => $value){
        if(is_null($value))
          unset($params[$key]);
        else
          $params[$key] = $value;
      }
    }
    return $params;
  }

  /**
   * Returns full uri including params and fragment.
   * @param array $params
   * @param bool $dontIncludeInternal
   * @return string
   */
  function getUri($params = array(), $dontIncludeInternal = false){

    $uri = $this->getUrl();

    $queryStr = $this->getQueryStr($params, $dontIncludeInternal);

    if(!empty($queryStr)) {
      $uri .= '?' . $queryStr;
    }

    if(!is_null($this->fragment)) {
      $uri .= '#' . $this->fragment;
    }
    return  $uri;
  }

  public function getHiddenFields($vars = array()){
    $params = $this->mergeParams($vars);

    $content = '';
    foreach($params as $key => $value){
      if(!is_null($value))
        $content .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />' . PHP_EOL;
    }
    return $content;
  }

  /**
   * Redirects to full uri with params in $vars added
   * @param array $vars
   * @param int $httpCode
   */
  public function redirect($vars = array(), $httpCode = 302){
    httpRedirect($this->getUri($this->mergeParams($vars)), $httpCode);
    exit;
  }

  public function getHtml() {
    return $this->getUri();
  }

  public function __toString() {
    return $this->getUri();
  }

}
