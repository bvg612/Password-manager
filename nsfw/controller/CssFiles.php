<?php
/**
 * User: npelov
 * Date: 22-10-17
 * Time: 2:05 PM
 */

namespace nsfw\controller;


class CssFiles extends PageHeadFiles {
  public function __construct($template = '') {
    parent::__construct($template);
  }

  function initTemplate() {
    return '<link rel="stylesheet" type="text/css" href="{%uri}"{%attrs}/>';
  }

  /**
   * @param array $attrs
   * @return string
   */
  protected function getAttrsHtml($attrs = []) {
    if(empty($attrs))
      return '';
    $html = '';
    foreach($attrs as $name=>$value) {
      if(empty($value))
        continue;
      $html .= ' '.$name.'="'.htmlspecialchars($value).'"';
    }
    return $html;
  }

  /**
   * @param string $cssFile
   * @param array $params Optional attributes [name=>value]
   */
  public function addFile(...$params) {
    $cssFile = array_shift($params);

    $this->addFileInternal($cssFile, [ 'attrs'=>$this->getAttrsHtml($params[0]) ]);
  }

}
