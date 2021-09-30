<?php

namespace nsfw\auth;


use Exception;
use MongoDB\BSON\Binary;

/**
 * Class PasswordHash
 *
 * Usage:
 * 1. call init() before creating any instance
 * 2. create an instance
 * 3. if you need to know how much storage to reserve for password hash call $this->getMaxPasswordHashLen()
 * 4. call passwordHash('<password>', ['<user email>']) to generate hash
 * 5. call checkPassword('<password>', '<password hash>');
 *
 * @package nsfw\auth
 */
class PasswordHash {

  const SHA1 = '11';
  const SHA1_10K = '21';
  const SHA224 = '12';
  const SHA256 = '32';
  const SHA384 = 'c4';
  const SHA512 = '6e';
  const SHA512_10K = '7e';
  const GOST = '86';
  const WHIRLPOOL = 'c7';
  const RIPEMD128 = 'e8';
  const RIPEMD160 = '69';
  const RIPEMD256 = 'aa';
  const RIPEMD320 = 'fb';

  // in characters. hexadecimal will be twice that number
  const MIN_SALT_LENGTH = 7;
  const MAX_SALT_LEN = 16;

  protected static $algos = array(
    self::SHA1_10K => 'sha1',
    self::SHA1 => 'sha1',
    self::SHA224 => 'sha224',
    self::SHA256 => 'sha256',
    self::SHA384 => 'sha384',
    self::SHA512_10K => 'sha512',
    self::SHA512 => 'sha512',//sha512x1000
    self::GOST => 'gost',
    self::WHIRLPOOL => 'whirlpool',
    self::RIPEMD128 => 'ripemd128',
    self::RIPEMD160 => 'ripemd160',
    self::RIPEMD256 => 'ripemd256',
    self::RIPEMD320 => 'ripemd320',
  );

  protected static $algoLens = array(
    'sha1' => 40,
    'sha224' => 56,
    'sha256' => 64,
    'sha384' => 96,
    'sha512' => 128,
    'gost' => 64,
    'whirlpool' => 128,
    'ripemd128' => 32,
    'ripemd160' => 40,
    'ripemd256' => 64,
    'ripemd320' => 80,
  );


  private $pepper;
  private $algoId = self::SHA1_10K;

  protected static $defaultPepper;
  protected static $defaultAlgoId = self::SHA1_10K;

  /**
   * @param string $pepper Default value for $this->pepper when object is created
   * @param string $algoId default hash method
   */
  public static function init($pepper, $algoId = self::SHA1_10K){
    self::$defaultPepper = $pepper;
    self::$defaultAlgoId = $algoId;
  }

  /**
   * PasswordHash constructor.
   * @param string $pepper pepper for password hash. Optional if default pepper is set by init();
   * @throws Exception
   */
  public function __construct($pepper = null) {
    $this->algoId = self::$defaultAlgoId;
    $this->pepper = self::$defaultPepper;
    if($pepper)
      $this->pepper = $pepper;
    if(empty($this->pepper)) {
      throw new Exception("pepper is not set!");
    }

    if(empty($this->algoId))
      throw new Exception("Algorithm is not selected!");

    if(self::getAlgoId($this->algoId) == false) {
      throw new Exception("Invalid algorithm ".$this->algoId."!");
    }
  }

  /**
   * @return string
   */
  public function getAlgoId() {
    return $this->algoId;
  }
  /**
   * @param string $algoId
   */
  public function setAlgoId($algoId) {
    $this->algoId = $algoId;
  }


  public static function getAlgoById($algoId){
    if(isset(self::$algos[$algoId]))
      return self::$algos[$algoId];
    return false;
  }

  public static function getIdByAlgo($algo){
    static $algoIds = null;
    if(is_null($algoIds))
      $algoIds = array_flip(self::$algos);
    if(isset($algoIds[$algo]))
      return $algoIds[$algo];
    return false;
  }

  public static function getHashLen($algo){
    if(isset(self::$algoLens[$algo]))
      return self::$algoLens[$algo];
    return false;
  }

  /**
   * Generates salt
   * @param int $minLen minimum size of bytes to be generated. One byte will take two characters in returned string.
   * @return string hexadecimal representation of salt
   */
  public static function genSalt($minLen = self::MIN_SALT_LENGTH){
    $maxLen = self::MAX_SALT_LEN;
    test(is_numeric($minLen));
    test($minLen > 0);
    if($minLen > $maxLen)
      $minLen = $maxLen;
    $len = rand((int)$minLen, (int)$maxLen);
    $salt = '';
    for($i = 0; $i < $len; $i++){
      $salt .= sprintf("%02x", rand(0,255));
    }
    return $salt;
  }


  /**
   * Returns hash of data using algorithm $algo
   * @param string $algo Algorithm. one of hash_algo() or openssl_get_md_methods(), or mhash - see code below
   *              for line $mhashAlgos = array( ...
   * @param string $data
   * @param bool $binary if true - turns binary checksum, if false - lower case hex string
   * @return bool|string
   */
  public static function hash($algo, $data, $binary = false){
    static $hashAlgos = null;
    static $mhashAlgos = null;
    static $opensslAlgos = null;

    $algo = strToLower($algo);

    if($algo == 'md5' && function_exists('md5')){
      //NOTE: php 5 required for raw output
      return $binary?md5($data, true):md5($data);
    }

    if($algo == 'sha1' && function_exists('sha1')){
      //NOTE: php 5 required for raw output
      return $binary?sha1($data, true):sha1($data);
    }

    if(is_null($hashAlgos)){
      if(function_exists('hash_algos') && function_exists('hash')){
        $hashAlgos = array_fill_keys(hash_algos(), true);
      }else{
        $hashAlgos = array();
      }
    }

    if(empty($hashAlgos) && array_key_exists($algo, $hashAlgos))
      return hash($algo, $data, $binary);

    // mhash
    if(is_null($mhashAlgos)){
      if(function_exists('mhash')){
        $mhashAlgos = array(
          'md2' => MHASH_MD2,
          'md4' => MHASH_MD4,
          'md5' => MHASH_MD5,
          'sha1' => MHASH_SHA1,
          'sha224' => MHASH_SHA224,
          'sha256' => MHASH_SHA256,
          'sha384' => MHASH_SHA384,
          'sha512' => MHASH_SHA512,
          'adler32' => MHASH_ADLER32,
          'crc32' => MHASH_CRC32,
          'crc32b' => MHASH_CRC32B,
          'gost' => MHASH_GOST,
          'haval128' => MHASH_HAVAL128,
          'whirlpool' => MHASH_WHIRLPOOL,
        );
      }else{
        $mhashAlgos = false;
      }
    }

    if($mhashAlgos && array_key_exists($algo, $mhashAlgos)){
      return $binary?mhash($mhashAlgos[$algo], $data):bin2hex(mhash($mhashAlgos[$algo], $data));
    }


    // OpenSSL
    if(is_null($opensslAlgos)){
      if(function_exists('openssl_get_md_methods') && function_exists('openssl_digest')){
        $opensslAlgos = openssl_get_md_methods(true);
      }else{
        $opensslAlgos = false;
      }
    }

    if($opensslAlgos && array_key_exists($algo, $opensslAlgos)){
      return $binary?hex2bin(openssl_digest($data, $algo)):openssl_digest($data, $algo);
    }

    throw new Exception('Could not find hash function for algorithm '.$algo);
    return false;
  }

  /**
   * Parses password hash and returns array with parts.
   * @param string $hash A hash generated by generatePasswordHash()
   * @return array Array of parts as follows:
   * 'algoId' - algorithm id
   * 'algo' - algorithm
   * 'hash' - the actual hash
   * 'salt' - the salt
   * @throws Exception
   */
  public static function parseHash($hash){
    $hash = strToLower($hash);
    if(!preg_match('/^[0-9a-z]{1,1000}[0-9a-z]{2}$/', $hash))
      throw new Exception("Bad password hash format '".$hash."'");
    $res = array();
    $res['algoId'] = substr($hash, -2, 2);
    $res['algo'] = self::getAlgoById($res['algoId']);
    $hashLen = self::getHashLen($res['algo']);
    if(!$hashLen)
      throw new Exception("Invalid hash length ".$hashLen." for algorithm ".$res['algo']);

    if(!preg_match('/^.{1,1000}[0-9a-z]{'.$hashLen.'}[0-9a-z]{2}$/', $hash))
      throw new Exception("Bad password hash format: '".$hash."'");

    $res['hash'] = substr($hash, -($hashLen+2), $hashLen);
    $res['salt'] = substr($hash, 0, -($hashLen+2));
    return $res;
  }

  public static function generatePasswordHash($algoId, $pass, $pepper, $salt = false){
    if(!$salt || strLen($salt)*2 < self::MIN_SALT_LENGTH)
      $salt = self::genSalt();
    $algo = self::getAlgoById($algoId);

    if(!$algo)
      throw new Exception("$algoId is not a valid hashing algorithm id.", E_USER_WARNING);

    $hash = self::hash($algo, $salt.$pass.$pepper);
    if($algoId == self::SHA1_10K || $algoId == self::SHA512_10K){
      // do 9,999 more rounds, 10,000 total. Every time use salt & pepper
      for($i=0;$i<10000;$i++){
        $hash = self::hash($algo, $salt.$hash.$pepper);
      }
    }
    return $salt.$hash.$algoId;
  }

  /**
   * Checks plain text password against hash
   * @param string $password The plain text password to be checked
   * @param string $hash A hash generated using passwordHash()
   * @param string $morePepper
   * @return bool true if password matches, false otherwise
   */
  public function checkPassword($password, $hash, $morePepper = ''){
    if(!is_string($password) || $password == null || $password == '' || strval($hash) == '')
      return false;
    $parsed = self::parseHash($hash);
    $builtHash = self::generatePasswordHash($parsed['algoId'], $password, $this->pepper.$morePepper, $parsed['salt']);
    return strToLower($builtHash) == strToLower($hash);
  }

  /**
   * Generates password hash
   * @param string $password
   * @param string $morePepper This could add user dependant pepper - like user id, username or email
   * @return bool|string
   */
  function passwordHash($password, $morePepper = ''){
    return self::generatePasswordHash($this->algoId, $password, $this->pepper . $morePepper, self::genSalt(10));
  }

  public static function getMaxPasswordHashLenForAlgoId($algoId) {
    $algo = self::getAlgoById($algoId);
    if($algo === false)
      throw new Exception("Invalid algorithm Id ".$algoId."!");
    $hashLen = self::getHashLen($algo);
    if($hashLen === false)
      throw new Exception("Invalid algorithm ".$algoId."!");

    return self::MAX_SALT_LEN*2 + $hashLen + 2;
  }

  /**
   * @return int returns maximum password hash length in characters, for current $this->algo
   */
  public function getMaxPasswordHashLen() {
    return self::getMaxPasswordHashLenForAlgoId($this->algoId);
  }


  public static function sha1($data, $binary = false) {
    return  PasswordHash::hash(self::SHA1, $data, $binary);
  }

  public static function sha256($data, $binary = false) {
    return  PasswordHash::hash(self::SHA256, $data, $binary);
  }

  public static function sha512($data, $binary = false) {
    return  PasswordHash::hash(self::getAlgoById(self::SHA512), $data, $binary);
  }
}
