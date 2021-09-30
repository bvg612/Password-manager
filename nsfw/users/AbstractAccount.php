<?php
/**
 * User: npelov
 * Date: 12-05-17
 * Time: 1:02 PM
 */

namespace nsfw\users;


use Exception;
use nsfw\database\Database;

abstract class AbstractAccount extends AbstractFieldObject {

  /**
   * @return bool
   * @throws Exception
   */
  public function register() {
    if($this->isRegistered())
      throw new Exception('Account is already registered (has an id)!');

    $fields = $this->exportToDb();
    unset($fields['id']);
    $id = $this->db->insert('accounts', $fields);
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
      SELECT * FROM accounts
        WHERE '.$db->escapeField($field).' = "'.$db->escape($value).'"
    ');
    if(empty($data))
      return null;
    $account = new static($db);
    $account->importDb($data);
    return $account;
  }

  public static function createById(Database $db, $id) {
    return self::createByField($db, 'id', $id);
  }

  public static function createByEmail(Database $db, $email) {
    return self::createByField($db, 'email', $email);
  }

}
