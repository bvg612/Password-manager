<?php

namespace nsfw\codegenerator;


class PhpClass {
  /** @var string */
  public $name;
  public $parent = '';
  /** @var array */
  public $interfaces = [];
  //public $openingBracketLevel = 0;

  public function getSimpleClassName() {
    $path = explode('\\', $this->name);
    return end($path);
  }

  public function appendToParent($str){
    if($this->parent == '\\' || $this->parent == '\\StdObject') {
      // we don't want forced leading \ because if namespace is imported it'll be prepended ... (ToDo)
      $this->parent = $str;
    } else {
      $this->parent .= $str;
    }
  }

  public function __sleep() {
    return [
      'name',
      'parent',
      'interfaces',
    ];
  }
}
