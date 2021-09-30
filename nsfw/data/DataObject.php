<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 2/11/2019
 * Time: 2:03 PM
 */

namespace nsfw\data;


use function nsfw\getPublicMembers;

/**
 * Class DataObject
 * @package data
 *
 * Add public members to use this object. It is intended to be used as array - no getters/setters or validation,
 * only pure data.
 */
abstract class DataObject {
  /**
   * DataObject constructor.
   *
   * @param array|null $data
   */
  public function __construct(array $data = null) {
    if(!empty($data))
      $this->import($data);
  }


  /**
   * @return array
   */
  public function export() {
    $data = getPublicMembers($this);
    foreach($data as $field=>&$value) {
      if($value instanceof DataObject) {
        $value = $value->export();
        continue;
      }
    }
    return $data;
  }

  /**
   * @return string|false
   */
  public function exportJson() {
    return json_encode($this->export());
  }


  protected function importField(&$item, $field, $value) {
    $importFunc = 'importField'.$field;

    if(method_exists($this, $importFunc)) {
      call_user_func([$this, $importFunc], $value);
      return;
    }

    if($this->$field instanceof DataObject) {
      $class = get_class($item);
      /** @var DataObject $item */
      $item = new $class;
      $item->import($value);
      return;
    }


    $this->$field = $value;
  }

  /**
   * @param array|object $draft
   */
  public function import($draft) {
    $fields = getPublicMembers($this);
    foreach($draft as $field=>$value) {
      if(!array_key_exists($field, $fields))
        throw new \LogicException('Invalid field '.$field.' in data object of class '.get_class($this));
      $this->importField($this->$field, $field, $value);
    }
  }

  /**
   * @param string $json
   */
  public function importJson($json) {
    $this->import(json_decode($json, true));
  }
}
