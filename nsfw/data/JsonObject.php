<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 1/10/2019
 * Time: 11:25 PM
 */

namespace nsfw\data;

use function nsfw\getPublicMembers;
use nsfw\template\DisplayObject;

class JsonObject extends \stdClass implements DisplayObject {
  private $priv = 'test';

  public function setField($name, $value) {
    $obj = $this;
    $obj->$name = $value;
  }

  public function getField($name) {
    $vars = getPublicMembers($this);
    return $vars[$name];
  }

  public function clear() {
    foreach($this as $field=>$value) {
      unset($this->$field);
    }
  }

  public function exportJson() {
    return json_encode($this);
  }

  public function importJson($json) {
    $this->import(json_decode($json));
  }

  /**
   * @param array|object $arrayOrObject
   */
  public function import($arrayOrObject) {
    foreach($arrayOrObject as $name=>$value) {
      $this->setField($name, $value);
    }
  }

  public function __toString() {
    return $this->exportJson();
  }

  function getHtml() {
    return $this->exportJson();
  }


}
