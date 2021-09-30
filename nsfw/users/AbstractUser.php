<?php
/**
 * User: npelov
 * Date: 12-05-17
 * Time: 1:01 PM
 */

namespace nsfw\users;

use Exception;
use nsfw\auth\PasswordHash;
use nsfw\controller\AbstractAction;
use nsfw\database\Database;

/**
 * Class AbstractUser
 *
 * @property int $id;
 * @property string $email;
 * @property string $password;
 * @property string $name;
 * @property string $gender;
 * @property string $createIp;
 * @property string $lastIp;
 * @property string $createDate;
 * @property string $lastLogin;
 * @property AbstractAccount $account;
 *
 * @package nsfw\users
 *
 */
abstract class AbstractUser extends AbstractFieldObject {
  /** @var AbstractAccount */
  protected $account;
  protected static $accountClass = '\nsfw\users\Account';

  /**
   * AbstractUser constructor.
   * @param Database $db
   * @param AbstractAccount $account
   */
  public function __construct(Database $db, AbstractAccount $account) {
    parent::__construct($db);
    $this->account = $account;
    $this->addFields([
      'email' => 'email',
      'password' => 'password',
      'name' => 'name',
      'gender' => 'gender',
      'createIp' => 'create_ip',
      'lastIp' => 'last_ip',
      'createDate' => 'create_date',
      'lastLogin' => 'last_login',
    ]);
    $this->fields['createIp'] = '00000000';
    $this->fields['lastIp'] = '00000000';
    if(!empty($_SERVER['REMOTE_ADDR'])) {
      $this->fields['createIp'] = $_SERVER['REMOTE_ADDR'];
    }
  }

  /**
   * @return string
   */
  public static function getAccountClass() {
    return self::$accountClass;
  }

  /**
   * @param string $accountClass
   */
  public static function setAccountClass($accountClass) {
    self::$accountClass = $accountClass;
  }



  public function getAccount() {
    return $this->account;
  }
  public function setAccount(AbstractAccount $account) {
    $this->account = $account;
  }

  protected function exportToDb() {
    $dbFields = parent::exportToDb();
    $dbFields['account_id'] = $this->account->id;
    return $dbFields;
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function register() {
    if($this->isRegistered())
      throw new Exception('User is already registered (has an id)!');

    $fields = $this->exportToDb();
    unset($fields['id']);
    $id = $this->db->insert('users', $fields);
    if(!empty($id))
      $this->fields['id'] = $id;
    return true;
  }

  /**
   * @param Database $db
   * @param $field
   * @param $value
   * @return static
   */
  public static function createByField(Database $db, $field, $value) {
    $data = $db->queryFirstRow('
      SELECT * FROM users
        WHERE '.$db->escapeField($field).' = "'.$db->escape($value).'"
    ');
    if(empty($data))
      return null;
    $aClass = self::getAccountClass();
    $account = call_user_func([$aClass, 'createById'], $db, $data['account_id']);
    if(empty($account))
      return null;
    $user = new static($db, $account);
    $user->importDb($data);
    test(!empty($user->id));
    return $user;
  }

  public static function createById(Database $db, $id) {
    return static::createByField($db, 'id', $id);
  }

  public static function createByEmail(Database $db, $email) {
    return static::createByField($db, 'email', $email);
  }

  public function checkPassword($password) {
    static $ph = null;
    if(empty($ph)){
      $ph = new PasswordHash();
    }

    return $ph->checkPassword($password, $this->password, $this->email);

  }
}
