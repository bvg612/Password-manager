<?php

namespace app\openssl;

class OpenSsl {
  const ENCRYPT_BLOCK_SIZE = 2048;
//  private $opensslConf = __DIR__.'\\ssl\\openssl.cnf';
  private $settigns = [];

  /**
   * OpenSsl constructor.
   *
   * @param array|null $settigns
   */
  public function __construct(array $settigns = null) {
    if(is_null($settigns))
    $this->settigns = [
//      "config" => $this->opensslConf,
      "digest_alg" => "sha512",
      'private_key_type'=>OPENSSL_KEYTYPE_RSA,
      'private_key_bits'=>4096,
    ];
  }


  /**
   * @param $plainData
   * @param string|OpenSslKey $key
   *
   * @return string
   */
  public function publicEncryptBase64($plainData,  $key) {
    return base64_encode($this->publicEncrypt($plainData, $key));
  }

  /**
   * @param $plainData
   * @param $key
   *
   * @return bool|string
   */
  public function publicEncrypt($plainData, $key) {
    assert(is_string($key)||($key instanceof OpenSslKey));
    if($key instanceof OpenSslKey)
      $key = $key->exportPublic();
    $encrypted = '';
    $keySize = 4096;
    $blockSize = $keySize/8 - 11;
    $chunks = str_split($plainData, $blockSize);
    foreach($chunks as $chunk) {
      $partialEncrypted = '';
      $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $key, OPENSSL_PKCS1_PADDING);
      if(!$encryptionOk) {
        var_dump(openssl_error_string());
        return false;
      }
      $encrypted .= $partialEncrypted;
    }
    return $encrypted;
  }

  public function privateDecryptBase64($data, OpenSslKey $key, $password = ''){
    return $this->privateDecrypt(base64_decode($data), $key, $password);
  }

  function privateDecrypt($data, OpenSslKey $key, $password = '') {
    $decrypted = '';

    $keySize = 4096;
    $blockSize = $keySize/8;
    //decode must be done before spliting for getting the binary String
    $data = str_split($data, $blockSize);

//    $privKey = openssl_get_privatekey($privatePEMKey, $password);
    $privKey = $key->getPrivate($password);
    if(!$privKey)
      return false;

    foreach($data as $chunk) {
      $partial = '';
      openssl_error_string();
      //be sure to match padding
      $decryptionOK = openssl_private_decrypt($chunk, $partial, $privKey, OPENSSL_PKCS1_PADDING);

      if($decryptionOK === false){
        var_dump("openssl_private_decrypt(): ". openssl_error_string());
        return false;
      }//here also processed errors in decryption. If too big this will be false
      $decrypted .= $partial;
    }
    return $decrypted;
  }


  function privateEncryptBase64($plainData, OpenSslKey $key, $password = '') {
    return base64_encode($this->privateEncrypt($plainData, $key, $password));
  }

  function privateEncrypt($plainData, OpenSslKey $key, $password = ''){
    $encrypted = '';
    $keySize = 4096;
    $blockSize = $keySize/8 - 11;
    $chunks = str_split($plainData, $blockSize);
    $privKey = $key->getPrivate($password);
    foreach($chunks as $chunk) {
      $partialEncrypted = '';
      $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $privKey, OPENSSL_PKCS1_PADDING);
      if(!$encryptionOk) {
        var_dump(openssl_error_string());
        return false;
      }
      $encrypted .= $partialEncrypted;
    }
    return $encrypted;
  }


  /**
   * @param $data
   * @param string|OpenSslKey $key
   *
   * @return bool|string
   */
  public function publicDecryptBase64($data, $key){
    return $this->publicDecrypt(base64_decode($data), $key);
  }

  function publicDecrypt($data, $key) {
    assert(is_string($key)||($key instanceof OpenSslKey));
    if($key instanceof OpenSslKey)
      $key = $key->exportPublic();

    $decrypted = '';
    $keySize = 4096;
    $blockSize = $keySize/8;
    //decode must be done before spliting for getting the binary String
    $data = str_split($data, $blockSize);

    foreach($data as $chunk) {
      $partial = '';

      //be sure to match padding
      $decryptionOK = openssl_public_decrypt($chunk, $partial, $key, OPENSSL_PKCS1_PADDING);

      if($decryptionOK === false){
        var_dump(openssl_error_string());
        return false;
      }//here also processed errors in decryption. If too big this will be false
      $decrypted .= $partial;
    }
    return $decrypted;
  }

}
