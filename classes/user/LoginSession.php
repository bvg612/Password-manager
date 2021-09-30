<?php


namespace app\user;


use app\db\Vars;
use app\openssl\OpenSslKey;
use nsfw\auth\PasswordHash;
use nsfw\database\Database;
use nsfw\exception\UserException;
use nsfw\session\DbSession;

class LoginSession {
  private $sessionSignSecret = 'hgOP83BPvNzFJBgzo91CkB64EO8Y';
  /** @var Vars */
  protected $vars;
  /** @var Database */
  protected $db;
  protected $userId = 0;
  /**
   * @var array|object
   */
  private $user;

  /**
   * LoginSession constructor.
   *
   * @param Database $db
   */
  public function __construct(Database $db) {
    srand(crc32(time()));
    $this->db = $db;
    $this->vars = new Vars($db);
  }

  /**
   * @return int
   */
  public function getUserId(): int {
    return $this->userId;
  }

  /**
   * @return array|object
   */
  public function getUser() {
    return $this->user;
  }

  public function isLogged() {
    $loggedUserId = getParam('user_id', 0, 'S');
    if(empty($loggedUserId))
      return false;
    $loggedCs = getParam('ss', '', 'C');
    $sessionSignCalced = $this->calcSessionSign($loggedUserId);
    if(empty($sessionSignCalced))
      return false;
    if($sessionSignCalced !== $loggedCs)
      return false;
    return true;
  }

  private function calcSessionSign($userId) {
    $sessionSignCalced = strtoupper(PasswordHash::hash(
      PasswordHash::getAlgoById(PasswordHash::SHA512),
      $userId.'-session-'.$this->sessionSignSecret
    ));
    return strtoupper($sessionSignCalced);
  }

  public function loadSession() {
    $ns = ns(); $db = $this->db;

    $userId = getParam('user_id', 0, 'S');

    if(empty($userId)) {
      $this->clearSession();
      return;
    }
    $user = $db->queryFirstRow('SELECT * FROM users WHERE id = '.intval($userId));
    if(empty($user))
      $this->invalidSession('User '.$userId.' not found');


    $ss = getParam('ss', '', 'C');
    if($ss != $this->calcSessionSign($user['id'])) {
      $this->invalidSession('wrong session sign');
    }

//    var_dump($_SESSION, $user['id'], $this->calcSessionSign($user['id']));exit;
    $key = new OpenSslKey();
    $keyPass = $this->rot13(getParam('kp', '', 'C'));
    $pemKey = getParam('key', '', 'S');

    if(empty($pemKey)) {
      $this->invalidSession('empty key');
    }
    if(empty($keyPass)) {
      $this->invalidSession('empty key password');
    }
    if(!$key->importPrivate($pemKey, $keyPass)) {
      $this->invalidSession('cannot import pem key');
    }
    $this->userId = $userId;
    $this->user = $user;
  }

  public function login($userId, $password) {
    $db = $this->db;
    $user = $db->queryFirstRow('SELECT * FROM users WHERE id = '.intval($userId));
    if(empty($user))
      throw new UserException('User '.$userId.' does not exist');
    $ph = new PasswordHash();//'RoZ20Ew4QXWnfhhaFLRBXmhLYx'

//    $passwordHash = $ph->passwordHash($password, $userId);
//    var_dump($passwordHash);

//    return false;

//    var_dump($user['password'], $ph->checkPassword($password, '5a46c95fb596fe7e8142089640d81d2880870de92afed4200ae3a7a17345757f9df207b7d7a244a48988f118af59297dbee7aafc2815e64133c99bcd07036255eee78af4fa594cb5be4d1346147e', $userId));

    if(!$ph->checkPassword($password, $user['password'], $userId))
      throw new UserException('Wrong password! Check Language, CapsLock, NumLock');

    $keyPassword = $this->generateRandomString(14,24);
//    $keyPassword = 'I.3Ifs.3Y7pu.2K.3YIl.7L52pob.26.3K.7KD.3I';

    $this->createPrivateKeyIfEmpty($user, $password);

    $key = new OpenSslKey();
    $key->importPrivate($user['key'], $password);
    $sessionKey = $key->exportPrivate($password, $keyPassword);
//    setcookie('kp', $this->rot13($keyPassword), time()-1, '/', '');
//    setcookie('kp_org', $this->rot13($keyPassword), time()-1, '/', '');


    setcookie('kp', $this->rot13($keyPassword), 0, '/', '');
    $_SESSION['key'] = $sessionKey;

    $_SESSION['user_id'] = $user['id'];
    setcookie('ss', $this->calcSessionSign($user['id']), 0, '/', '');
    return true;
  }

  public function isAdmin() {
    return $this->user['admin'];
  }

  protected function createPrivateKeyIfEmpty(&$user, $password) {
    $ns = ns(); $db = $ns->db;

    if(!empty($user['key'])) {
      return;
    }
    $key = new OpenSslKey();
    $key->generate();
    $key->setPassword($password);
    $user['key'] = $key->exportPrivate($password, $password);
    $db->simpleUpdate('users', [
      'id' =>$user['id'],
      'key' => $user['key'],
    ]);
  }

  public function rot13($t) {
    //xjftzugrdemyvqwnoihacpklbs
    //VFWIPGRULYCHQXSNDZJOABEKMT
    $coding = 'xjftzugrdemyvqwnoihacpklbsxjftzugrdemyvVFWIPGRULYCHQXSNDZJOABEKMTVFWIPGRULYCHQ_"%@!#$^&*()-+=.,<>/?\|`~{_"%@!#$^&*()-';
    for ($r = '',$i=0;$i<strlen($t);$i++) {
      $character = $t{$i};
      $position = strpos($coding,$character);

      if ($position !== false) $character = $coding{$position + 13};
      $r.=$character;
    }
    return $r;
  }

  public function generateRandomString($length = 12, $lengthTo = null) {
    if(!is_null($lengthTo))
      $length = rand($length, $lengthTo);

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_"%@!#$^&*()-+=.,<>/?\|`~{';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[$this->rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  public function rand($min, $max) {
    if (PHP_VERSION_ID >70000)
      return random_int($min, $max);
    return rand($min, $max);
  }

  public function logout() {
    $ns = ns(); $db = $ns->db;
    $this->clearSession();
    setcookie('PHPSESSID', '', time()-1, '/', '');
    $ns->session->destroy();
  }

  public function clearSession() {
    setcookie('ss', '', time()-1, '/', '');
    setcookie('kp', '', time()-1, '/', '');
    unset($_SESSION['user_id']);
  }

  private function invalidSession($message = 'Unknown reason') {
    $ns = ns();

    $this->clearSession();

    $ns->errorReporter->errorRedirect('/', 'Invalid Session: '.$message);
  }
}
