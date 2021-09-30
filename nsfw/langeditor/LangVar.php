<?php
/**
 * User: npelov
 * Date: 07-07-17
 * Time: 12:49 AM
 */

namespace nsfw\langeditor;


class LangVar {
  /** @var int */
  public $id;
  /** @var string */
  public $name;
  /** @var string */
  public $translation;
  /** @var string */
  public $description;

  /**
   * LangVar constructor.
   * @param array|object $row
   */
  public function __construct($row = null) {
    if(!empty($row))
      $this->import($row);
  }

  /**
   * @param array|object $row
   */
  public function import($row) {
    if(is_array($row)) {
      $this->id = $row['id'];
      $this->name = $row['var_name'];
      $this->translation = $row['translation'];
      $this->description = $row['description'];
    } else if(is_object($row)) {
      $this->id = $row->id;
      $this->name = $row->name;
      $this->translation = $row->translation;
      $this->description = $row->description;
    }
    if(empty($this->description))
      $this->description = '';
    if(empty($this->translation))
      $this->translation = '';
  }
}
