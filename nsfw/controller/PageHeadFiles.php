<?php

namespace nsfw\controller;

use nsfw\template\AbstractDisplayObject;

abstract class PageHeadFiles extends PageBlock {
  /** @var string  */
  protected $template = '<link rel="stylesheet" type="text/css" href="{%uri}"{%attrs}/>';
  /** @var array */
  protected $files = [];

  /**
   * PageHeadFiles constructor.
   * @param string $template
   */
  public function __construct($template = '') {
    if(!empty($template))
      $this->template = $template;
    else
      $this->template = $this->initTemplate();
  }

  protected function addFileInternal($file, array $attrs = []) {
    $this->files[$file] = $attrs;
  }

  /**
   * @return string
   */
  abstract function initTemplate();

  /**
   * This should be custom implementation for each inheritor with arbitrary params. To match the parent method
   * additional params must be optional
   * @param array $params
   * @return string
   */
  abstract function addFile(...$params); //

  /**
   * @param string $var
   * @param array $key
   */
  private function varNameToTplVar(&$var, $key) {
    $var = '{%'.$var.'}';
  }

  public function getFileHtml($file, array $attrs) {
    $search = array_keys($attrs);
    $replace = array_values($attrs);
//    if($this instanceof CssFiles) {
//      var_dump($file, $attrs, $search, $replace);
//      exit;
//    }
    array_walk($search, [$this, 'varNameToTplVar']);
    $search[] = '{%uri}';
    $replace[] = $file;
    return str_replace($search, $replace, $this->template);
  }

  public function getHtml() {
    $html = '';

    foreach($this->files as $file=>$attrs) {
      $html .= $this->getFileHtml($file, $attrs);
    }
    return $html;
  }
}
