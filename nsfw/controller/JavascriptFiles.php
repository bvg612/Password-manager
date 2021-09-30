<?php
/**
 * User: npelov
 * Date: 22-10-17
 * Time: 2:11 PM
 */

namespace nsfw\controller;


class JavascriptFiles  extends PageHeadFiles {
  public function __construct($template = '') {
    parent::__construct($template);
  }


  /**
   * @return string
   */
  function initTemplate() {
    return '<script type="text/javascript" src="{%uri}"></script>';
  }

  /**
   * @param string $jsFile
   */
  public function addFile(...$params) {
    $jsFile = array_shift($params);
    $this->addFileInternal($jsFile);
  }

  public function dump() {
    var_dump($this->files);
  }


}
