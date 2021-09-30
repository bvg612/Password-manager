<?php
/**
 * User: npelov
 * Date: 02-07-17
 * Time: 4:35 PM
 */

namespace nsfw\i18;


use nsfw\database\Database;

class LanguageData {
  /** @var Database */
  protected $db;

  /**
   * LanguageData constructor.
   * @param Database $db
   */
  public function __construct(Database $db) {
    $this->db = $db;
  }

  public function createTables() {
  }

}
