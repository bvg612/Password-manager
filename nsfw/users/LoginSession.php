<?php
/**
 * User: npelov
 * Date: 12-05-17
 * Time: 5:37 PM
 */

namespace nsfw\users;


use nsfw\database\Database;
use nsfw\session\Session;

/**
 * Class LoginSession
 * @property AbstractUser $user
 * @property AbstractAccount $account
 * @property int $userId
 * @property int $accountId
 * @package nsfw\users
 */
class LoginSession {
  /** @var Database */
  protected $db;
  protected $session;
  protected $user;
  protected $userClass = '\\nsfw\\users\\User';

  /**
   * LoginSession constructor.
   * @param Database $db
   * @param Session $session
   */
  public function __construct(Database $db, Session $session) {
    $this->db = $db;
    $this->session = $session;
  }

  /**
   * @param string $userClass
   */
  public function setUserClass($userClass) {
    $this->userClass = $userClass;
  }

  /**
   * @return bool
   */
  public function isLogged() {
    return !empty($this->user);
  }

  /**
   * @return bool true if logged in, false otherwise
   */
  public function loadSession() {
    $userId = $this->session->get('uid', 0);
    //var_dump('session uid: ', $userId);
    if(empty($userId))
      return false;
    $class = $this->userClass;
    /** @var AbstractUser $user */
    $user =  call_user_func([$class, 'createById'], $this->db, $userId);
    if(empty($user)) {
      $this->session->delete('uid');
      return false;
    }
    $this->user = $user;
    $this->session->set('uid', $user->id);
    return true;
  }

  /**
   * User login without password check. Does not check if user actually exists!!!
   *
   * @param AbstractUser $user
   * @return bool
   */
  protected function internalLogin($user) {
    $this->user = $user;
    $this->session->set('uid', $user->id);
    return true;
  }

  public function login($email, $password) {
    $class = $this->userClass;
    /** @var AbstractUser $user */
    $user =  call_user_func([$class, 'createByEmail'], $this->db, $email);
    if(empty($user))
      return false;
    if(!$user->checkPassword($password))
      return false;
    return $this->internalLogin($user);
  }

  public function logout() {
    $this->session->delete('uid');
    $this->user = null;
  }

  public function __isset($name) {
    static $magic = [
      'user' => true,
      'account' => true,
      'userId' => true,
      'accountId' => true,
    ];
    return array_key_exists($name, $magic);
  }

  public function __get($name) {
    $user = $this->user;
    switch($name) {
      case 'user': return $this->user;
      case 'account': return $user->account;
      case 'userId': return $this->user->id;
      case 'accountId': return $user->account->id;
    }
    return null;
  }
}
