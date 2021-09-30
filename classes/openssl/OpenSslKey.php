<?php


namespace app\openssl;


class OpenSslKey {
  private $password = "";
  /** @var resource|null */
  private $key;
  public $openSslSettings = [
    //    "config" => $opensslConf,
    "digest_alg" => "sha512",
    'private_key_type'=>OPENSSL_KEYTYPE_RSA,
    'private_key_bits'=>4096,
  ];

  /**
   * @param mixed $password
   */
  public function setPassword($password): void {
    $this->password = $password;
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function generate() {
    if($this->key != null)
      throw new \Exception('Key is already assigned. Cannot modify key');
    $key = $this->key = openssl_pkey_new($this->openSslSettings);
    if(!$key) {
      var_dump(openssl_error_string());
      return false;
    }
    return true;
  }

  /**
   * @param string $password
   *
   * @return bool|resource
   */
  public function getPrivate($password) {
    if($this->password !== $password)
      return false;
    return $this->key;
  }

  public function importPrivate($pemKey, $password = '') {
    if(!is_null($this->key))
      openssl_pkey_free($this->key);
    $key = openssl_pkey_get_private($pemKey, $password);
    if(empty($key))
      return false;
    $this->key = $key;
    $this->setPassword($password);
    return true;
  }

  public function exportPrivate($password, $exportPass = '') {
    if($password !== $this->password)
      return false;
    if(empty($exportPass))
      $exportPass = null;
//    if(is_null($password))
//      $password = $this->password;
    if(!openssl_pkey_export($this->key, $privKey, $exportPass, $this->openSslSettings)) {
      var_dump(openssl_error_string());
      return false;
    }
    return $privKey;
  }

  public function exportPublic() {
    // Extract the public key from $res to $pubKey
    $keyInfo = openssl_pkey_get_details($this->key);
    if(!$keyInfo)
      return false;
    return $keyInfo["key"];
  }

  public function free() {
    if(is_null($this->key))
      return false;
    openssl_pkey_free($this->key);
    $this->key = null;
    return true;
  }
}
