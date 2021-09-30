<?php
/**
 * User: npelov
 * Date: 22-10-17
 * Time: 2:42 PM
 */

namespace nsfw\controller;


class PageInfoBlock extends PageBlock {
  protected $title = '';
  protected $description = '';
  protected $keywords = [];
  protected $otherMeta = [];

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  public function addMeta($name, $content) {
    $this->otherMeta[] = [
      'name'=>$name,
      'content'=>$content,
    ];
  }

  public function escapeArr(&$item, $key, $data = '') {
    $item = htmlspecialchars($item);
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * @param string $keyword
   */
  public function addKeyword($keyword) {
    if(empty($keyword))
      return;
    $this->keywords[$keyword] = $keyword;
  }

  /**
   * @param array $keywords
   */
  public function addKeywords(array $keywords) {
    foreach($keywords as $kw) {
      $this->addKeyword($kw);
    }
  }

  protected function getMetaHtml() {
    $metaHtml = '';
    foreach($this->otherMeta as $meta) {
      if(empty($meta))
        continue;
      $tag = '<meta';
      foreach($meta as $attrName=>$value) {
        $tag .= ' '.$attrName.'="'.htmlspecialchars($value).'"';
      }
      $tag .= " />\r\n";
      $metaHtml .= $tag;
    }
    return $metaHtml;
  }

  function getHtml() {
    $html = '';
    if(!empty($this->title))
      $html .= '<title>'.htmlspecialchars($this->title).'</title>';
    if(!empty($this->description))
      $html .= '<meta name="description" content="'.htmlspecialchars($this->description).'">';
    $keywords = $this->keywords;
    array_walk($keywords, [$this, 'escapeArr']);
    if(!empty($this->keywords))
      $html .= '<meta name="keywords" content="'.htmlspecialchars(implode(',',$keywords)).'">';
    $html.= $this->getMetaHtml();
    return $html;
  }
}
