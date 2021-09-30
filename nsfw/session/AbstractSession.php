<?php

namespace nsfw\session;


use Exception;

abstract class AbstractSession implements Session {
  /** @var AbstractSession */
  protected static $instance;

  protected $sessionId = null;
  /** @var int */
  protected $ip;
  protected $autoclose = true;

  /**
   * @var int time in seconds to delete inactive sessions or null for native php time see session.gc_maxlifetime in php.ini
   */
  protected $gcLivetimeOverride = null;

  function getSessionId(){
    return $this->sessionId;
  }


  public function __construct(){
  }

  public function __destruct(){
    if($this->autoclose)
      $this->close();
  }


  function isStarted(){
    return !is_null($this->sessionId);
  }

  /**
   * @return bool
   */
  public function isAutoclose() {
    return $this->autoclose;
  }

  /**
   * @param bool $autoclose
   */
  public function setAutoclose($autoclose) {
    $this->autoclose = $autoclose;
  }


  static public function getInstance($reset = false){
    if($reset){
      if(!is_null(self::$instance))
        self::$instance->close();
      self::$instance = null;
    }
    if(is_null(self::$instance)){
      self::$instance = new static();
    }
    return self::$instance;
  }

  /**
   * @return int
   */
  public function getGcLivetime(){
    return $this->gcLivetimeOverride;
  }

  /**
   * Overrides the lifetime of the session (not the cookie).
   *
   * @param int $time time in seconds to delete inactive sessions or null to use value defined in php.ini
   * @see session.gc_maxlifetime in php.ini
   */
  public function setGcLivetime($time){
    if(!is_numeric($time) || $time < 1){
      trigger_error('nsSession::setGcLivetime() parameter must be positive number', E_USER_ERROR);
      return;
    }

    if(false && $time<24*3600){
      trigger_error('nsSession::setGcLivetime() time should be greater than 24 hours (86400 seconds). '.$time.' passed.', E_USER_WARNING);
    }
    $this->gcLivetimeOverride = $time;
  }


  /**
   * @param int|array $paramsOrLifetime
   * @param null $path
   * @param string $domain
   * @param bool $secure
   * @param bool $httponly
   */
  public function setCookieParams($paramsOrLifetime, $path = null, $domain ='', $secure = false, $httponly = false){
    if (defined('PHP_VERSION_ID')) {
      if (PHP_VERSION_ID >70000 && (is_array($paramsOrLifetime) || $paramsOrLifetime instanceof CookieParams)) {
        $this->setCookieParams7($paramsOrLifetime);
        return;
      }
    }
    if($paramsOrLifetime instanceof CookieParams) {
      $cp = $paramsOrLifetime;
      $path .= ';SameSite=' . $cp->sameSite;
      session_set_cookie_params($cp->lifeTime, $path, $cp->domain, $cp->secure, $cp->httpOnly);
    } else if(is_array($paramsOrLifetime)) {
      session_set_cookie_params($paramsOrLifetime, $path, $domain, $secure, $httponly);
      call_user_func_array('session_set_cookie_params', $paramsOrLifetime);
    }else {
      session_set_cookie_params($paramsOrLifetime, $path, $domain, $secure, $httponly);
    }
  }

  /**
   * @param array|CookieParams $params
   */
  private function setCookieParams7($params) {
    if($params instanceof CookieParams)
      $params = $params->getCookieParamsArray();
    session_set_cookie_params($params);
  }


  public function start(){
    $this->setHandler();
    @session_start();
    $this->sessionId = session_id();
  }

  abstract protected function setHandler();

  public function delete($name){
    unset($_SESSION[$name]);
  }

  public function has($name){
    return isset($_SESSION[$name]);
  }

  public function get($name, $default = null){
    if(!$this->has($name))
      return $default;
    return $_SESSION[$name];
  }

  public function set($name, $var){
    $_SESSION[$name] = $var;
  }

  public function close(){
    session_write_close();
    $this->sessionId = null;
  }

  public function destroy(){
    session_destroy();
    $this->sessionId = null;
  }

  public function __set($name, $var){
    $this->set($name, $var);
  }

  public function __get($name){
    return $this->get($name);
  }

  public function __unset($name){
    $this->delete($name);
  }

  public function __isset($name){
    return isset($_SESSION[$name]);
  }

}
