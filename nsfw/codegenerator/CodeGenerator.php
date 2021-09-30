<?php

namespace nsfw\codegenerator;


class CodeGenerator {
  protected $classes = [];
  protected $classDirs = [];

  protected function collectClassesForDir($classDir) {
    $dir = dir($classDir);
    while (false !== ($entry = $dir->read())) {
      if($entry == '.' || $entry == '..')
        continue;

      echo $entry."\n";
      $tokens = token_get_all(file_get_contents($classDir.'/'.$entry));
    }
    $dir->close();
  }

  public function collectClasses() {
    foreach($this->classDirs as $classDir) {
      $this->collectClassesForDir($classDir);
    }
  }
  public function generate() {
    $ci = new ClassInspector();
    //$classes = $ci->collectClasses($this->classDirs);
  }
}
