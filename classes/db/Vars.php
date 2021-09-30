<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 1/26/2019
 * Time: 11:28 AM
 */

namespace app\db;


use nsfw\database\Database;

class Vars {
  public static $table = 'vars';
  /** @var Vars */
  private static $instance;
  /** @var Database */
  private $db;

  /**
   * Vars constructor.
   *
   * @param Database|null $db
   */
  public function __construct(Database $db = null) {
    if(empty($db))
      $db = ns()->db;
    $this->db = $db;
  }

  public static function getInstance(Database $db = null) {
    if(empty($instance)) {
      static::$instance = new static($db);
    }

    return static::$instance;
  }

  /**
   * @param string $name
   * @param bool $lock
   *
   * @return mixed|string
   * @throws \nsfw\database\dbException
   */
  public function get($name, $lock = false) {
    return $this->db->queryFirstField('SELECT value FROM '.self::$table.' WHERE name = "'.$this->db->escape($name).'"');
  }

  /**
   * @param string $name
   * @param string $value
   *
   * @throws \nsfw\database\dbException
   */
  public function set($name, $value) {
    $db = $this->db;
    $db->insertUpdate(self::$table, ['name'=>$name, 'value'=>$value], 'name', false);
  }


}
