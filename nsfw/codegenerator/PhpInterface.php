<?php

namespace nsfw\codegenerator;


class PhpInterface {
  public $name = '';
  public $parents = [];
  //public $openingBracketLevel = 0;
  public $currentParent = '\\';

  public function appendToParent($str){
    if($this->currentParent == '\\') {
      // we don't want forced leading \ because if namespace is imported it'll be prepended ... (ToDo)
      $this->currentParent = $str;
    } else {
      $this->currentParent .= $str;
    }
  }


  public function __sleep() {
    return [
      'name',
      'parents',
    ];
  }

}
