<?php


namespace nsfw\session;


class CookieParams {
  /**
   * @var int
   */
  public $lifeTime = 0;
  public $path = '/';
  public $domain = '';
  public $secure = false;
  public $httpOnly = false;
  /**
   * @var string 'Strict', 'Lax', 'None'
   */
  public $sameSite = 'Lax';

  public function getCookieParamsArray() {
    return [
      'lifetime' => $this->lifeTime,
      'path' => $this->path,
      'domain' => $this->domain,
      'secure' => $this->secure,
      'httponly' => $this->httpOnly,
      'samesite' => $this->sameSite,
    ];

  }
}
