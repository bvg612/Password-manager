<?php


namespace app\db;

use nsfw\database\Database;
use nsfw\database\DbRow;

/**
 * Class Record
 * @package app\db
 *
 * @method static Record createById(Database $db, $id)
 * @method static Record createByField(Database $db, $field, $value)
 *
 */
class Record extends DbRow {
  protected static $dbTable = 'records';
  /** @var int */
  public $id;
  /** @var int */
  public $userId;
  /** @var int */
  public $catId;
  /** @var string */
  public $title;
  /** @var string */
  public $login;
  /** @var string */
  public $url;
  /** @var string */
  public $password;
  /** @var string */
  public $description;
  /** @var string */
  public $secureNote;
  /** @var string */
  public $hash;
}
