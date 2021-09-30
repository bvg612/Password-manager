<?php

namespace nsfw\database;


use Exception;
use nsfw\Singleton;

class IdGen extends Singleton {
  /** @var Database */
  protected $db;
  /** @var string */
  protected $idTableName = 'id_gen';

  /**
   * @param array $args
   * @return IdGen
   *
   * @throws Exception
   */
  public static function getInstance($args = []) {
    if(!self::hasInstance())
      throw new Exception('You must create instance using IdGen::createInstance() before you can use it');
    return parent::getInstance();
  }

  public static function createDb(Database $db) {
    $xi = new XmlImporter($db);
    $xi->import(NSFW_BASE_DIR . '/data/db/id_gen.xml');
  }

  /**
   * @param Database $db
   * @param bool $new
   * @return IdGen
   * @throws Exception
   */
  public static function createInstance(Database $db, $new = false) {
    if(!$new) {
      if(self::hasInstance())
        throw new Exception(' IdGen instance has already been created');
    }
    /** @var IdGen $instance */
    $instance = parent::newInstance();
    $instance->db = $db;
    return $instance;
  }

  /**
   * @param int $name The name of the id
   * @param bool $transaction true to start/commit transaction, false if caller started the transaction. Transaction is
   * mandatory!!!
   * @return int|mixed
   * @throws Exception
   */
  public function nextId($name, $transaction = false) {
    if(empty($name))
      throw new Exception("name cannot be empty");

    $db = $this->db;
    if($transaction)
      $db->startTransaction();
    try {
      $nextId = $db->queryFirstField('
        SELECT next_id FROM
          ' . $this->idTableName . '
          WHERE name = "' . $db->escape($name) . '"
          FOR UPDATE
      ');
      if(empty($nextId)) {
        $nextId = 1;
        $db->query('
          INSERT INTO ' . $this->idTableName . '(name, next_id)
            VALUES("' . $db->escape($name) . '", '.intval($nextId).')
        ');
      }else {
        $db->query('
          UPDATE ' . $this->idTableName . '
            SET next_id = "'.$db->escape($nextId+1).'" WHERE name = "' . $db->escape($name) . '"
        ');
      }
      if($transaction)
        $db->commit();
      return $nextId;
    } catch (Exception $e) {
      if($transaction)
        $db->rollback();
      throw $e;
    }

  }

  public function resetId($name, $value = 1) {
    if(empty($name))
      throw new Exception("name cannot be empty");

    if(empty($value))
      $value = 1;

    $this->db->query('
        UPDATE ' . $this->idTableName . '
          SET next_id = "'.$this->db->escape($value).'" WHERE name = "' . $this->db->escape($name) . '"
      ');
  }
}
