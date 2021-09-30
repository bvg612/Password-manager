<?php
/**
 * User: npelov
 * Date: 15-05-17
 * Time: 11:51 AM
 */

namespace nsfw\users;


class Permission {
  public $id;
  public $name;
  public $object;

  public $hasPermission = false;

  public function __construct(array $data = null) {
    if(!empty($data)) {
      $this->id = $data['id'];
      $this->name = $data['name'];
      $this->object = $data['object'];
      if(!is_null($data['ref_id']))
        $this->hasPermission = true;
    }
  }
}
