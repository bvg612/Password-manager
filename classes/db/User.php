<?php


namespace app\db;


use nsfw\database\Database;
use nsfw\database\DbRow;

/**
 * Class User
 * @package app\db
 *
 * @method static User createById(Database $db, $id)
 * @method static User createByField(Database $db, $field, $value)
 *
 */
class User extends DbRow {
  protected static $dbTable = 'users';
  /** @var int */
  public $id;
  /** @var string */
  public $name;
  /** @var string */
  public $email;
  /** @var string */
  public $password;
  /** @var boolean */
  public $admin;
  /** @var string */
  public $key;
  /** @var string */
  public $openKey;
}
