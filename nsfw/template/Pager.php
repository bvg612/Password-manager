<?php

namespace nsfw\template;


use nsfw\uri\Url;

class Pager extends AbstractDisplayObject {
  public $total = 0;
  public $offset = 0;
  public $perPage = 30;
  /** @var Url */
  protected $url = null; // nsPageUrl

  public static $defaultTemplate = '
  <div class="pageNumbers">
    <a href="{%prevPage">{%prevPageText}</a>
    <block pageNumber><a href="{%link}"><span>{%text}</span></a></block>
    <a href="{%nextPage">{%nextPageText}</a>
  </div>
  ';

  public $queryVar = 'from';
  public $onePageEmpty = true;

  public static $defaultVisibleNumbers = 20;

  public $visibleNumbers = 0;
  public static $template = '
  <div class="pageNumbers">
    <block firstPage>
      <a href="{%link}" class="eb first {%class}">{%text}</a>
    </block>
    <block prevPage>
      <a href="{%link}" class="eb prev {%class}">{%text}</a>
    </block>
    <block pageNumber><block separator> | </block><a href="{%link}" {%attr}><span>{%text}</span></a></block>
    <block nextPage>
      <a href="{%link}" class="eb next {%class}">{%text}</a>
    </block>
    <block lastPage>
      <a href="{%link}" class="eb last {%class}">{%text}</a>
    </block>
  </div>
    ';

  /**
   * Pager constructor.
   * @param Url|string $url
   */
  function __construct($url = './'){
    if (is_string($url))
      $url = new Url($url);
    if(! $url instanceof \nsfw\uri\Url)
      trigger_error('$url parameter must be string or instance of nsfw\\uri\\Url');
    $this->url = $url;
    $this->offset = intval($this->url->getParam($this->queryVar, 0));
    if (empty($this->visibleNumbers))
      $this->visibleNumbers = self::$defaultVisibleNumbers;
    $this->offset = getParam($this->queryVar, 0);
  }

  /**
   * @return Url
   */
  public function getUrl() {
    return $this->url;
  }

  private function getNumberBlock($tpl, $text, $link, $selected){
    $numberBlock = $selected ? $tpl->selectedNumber : $tpl->number;
    $numberBlock->text = $text;
    $numberBlock->link = $link;

    return $numberBlock->getParsed(true);
  }
  function getHtml() {
    $tpl = new CascadedTemplate(self::$template);
    $bFirstPage = $tpl->getBlock('firstPage');
    $bLastPage = $tpl->getBlock('lastPage');
    $bPrevPage = $tpl->getBlock('prevPage');
    $bNextPage = $tpl->getBlock('nextPage');

    $bFirstPage->text = ' |&lt; ';
    $bLastPage->text = ' &gt;| ';
    $bPrevPage->text = ' &lt; ';
    $bNextPage->text = ' &gt; ';

    if($this->total<$this->perPage){
      if($this->onePageEmpty)
        return '';
      else
        return '';
    }


    $prev = $this->offset - $this->perPage;
    if($prev < 0)
      $prev = 0;

    $next = $this->offset + $this->perPage;
    if($next >= $this->total)
      $next=0;

    $numPages = (int)ceil($this->total / $this->perPage);
    if($numPages <= 1)
      return '';


    $curPage = (int)floor($this->offset / $this->perPage);

    $bPrevPage->setVisible($this->offset!=0);
    $bPrevPage->setVar('link', $this->url->getUri(array($this->queryVar => $prev)));


    if($this->offset!=0){
//      $content .= str_replace(
//        array('{%text}', '{%link}'),
//        array($this->prevText, $this->url->getUri(array($this->queryVar => $prev))),
//        $this->offset==0?$this->inactivePNTemplate:$this->activePNTemplate
//      );
    }

    $minPage = $curPage - ceil($this->visibleNumbers/2)+1;
    if($minPage < 0)
      $minPage = 0;

    $maxPage = $minPage + $this->visibleNumbers;
    if($maxPage > $numPages){
      $maxPage = $numPages;
      $minPage = $numPages - $this->visibleNumbers;
      if($minPage < 0)
        $minPage = 0;
    }


    $bNumbers = $tpl->getBlock('pageNumber');
    for($i=$minPage;$i<$maxPage;$i++){
      $row = $bNumbers->appendRow([
        'text' => $i+1,
        'link' => $this->url->getUri(array($this->queryVar => $i*$this->perPage)),
      ]);
      $row->getBlock('separator')->setVisible($minPage != $i);
      if ($i == $curPage) {
        $row->setVar('attr', ' class="selected" disabled="disabled"');
        $row->setVar('link', 'javascript:;');
      }
//      $numbersContent .= $this->getNumberBlock(
//        $tpl,
//        $i+1,
//        $this->url->getUri(array($this->queryVar => $i*$this->perPage)),
//        $i == $curPage
//      );

//      if($i == $curPage){
//        $content .= str_replace('{%text}', $i+1, $this->inactivePageTemplate);
//      }else{
//        $content .= str_replace(
//          array('{%text}', '{%link}'),
//          array($i+1, $this->url->getUri(array($this->queryVar => $i*$this->perPage))),
//          $this->activePageTemplate
//        );
//      }
    }

    $bNextPage->setVar('link', $this->url->getUri(array($this->queryVar => $next)));
    $bNextPage->setVisible($next!=0);

    $bFirstPage->setVisible($this->offset!=0);
    $bFirstPage->setVar('link', $this->url->getUri(array($this->queryVar => 0)));
    $bLastPage->setVisible($next!=0);
    $bLastPage->setVar('link', $this->url->getUri(array($this->queryVar => ($numPages-1)*$this->perPage)));

//    $tpl->setField('block:number', $numbersContent);
    return $tpl->getParsed();

  }

  public function getPageNumbers(){
    return $this->getHtml();
  }
  public function __toString(){
    return $this->getHtml();
  }
}
