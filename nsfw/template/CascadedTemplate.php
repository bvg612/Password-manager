<?php

namespace nsfw\template;
use InvalidArgumentException;
use mysql_xdevapi\Exception;
use nsfw\cache\Cache;
use nsfw\database\Statement;

/**
 * Class CascadedTemplate
 *
 * Variable format: {%varname}
 * Fix temlpate path: {%tpl:/path/to/file}
 * translate path relative to site root: {%web:/path/to/file}
 *
 * variables look like
 *
 * The template has sub blocks. Every block can have rows - which is the same block multiple times with different data
 *
 * @package nsfw\template
 *
 * @method static CascadedTemplate createFromFile($filename)
 *
 */
class CascadedTemplate extends AbstractTemplate {
  public $id;
  private $path;
  private $siblingIndex = 1;

  /** @var  array */
  protected $vars = [];
  /** @var  string */
  protected $template;

  /** @var string */
  protected $name;
  /** @var CascadedTemplate */
  protected $parent;

  /** @var CascadedTemplate A pointer to next "row" */
  protected $next;
  /** @var CascadedTemplate A pointer to first "row" */
  protected $first;
  /** @var CascadedTemplate A pointer to currently processing row. Null if processRow never called */
  protected $processingRow;

  /** @var bool */
  protected $visible = true;

  /** @var TemplateCache */
  private $tempalteCache;

  /**
   * CascadedTemplate constructor.
   * @param string|TemplateCache|CascadedTemplate|TemplateConfig $template If this is string - template is loaded
   * from the string. If it's TemplateCache - loading is faster (as TemplateCache is the compiled template). If it's
   * CascadedTemplate, it's passed as first or of the template being created.
   *
   * ToDo: Can't pass cache every time. Think of some way of setting cache once (TemplateConfig?) and maybe remove cache param
   * ToDo: There should be a way to reset template cache - set the newest template cache timestamp
   *
   * @param Cache $cache
   */
  public function __construct($template = '', Cache $cache = null) {
    $this->id = self::getNextId();
    $templateStr = $template;
    if($template instanceof TemplateConfig) {
      $this->config = $template;
      $templateStr = '';
    }

    if(empty($this->config)) {
      $this->config = static::getDefaultConfig();
    }

    $this->cache = $this->config->cache;


    $this->filePathProcessor = new FilePathProcessor($this->config);
    $this->preProcessors[] = $this->filePathProcessor;

    if($template instanceof CascadedTemplate) {

      $this->first = $template;
      $this->siblingIndex = $template->siblingIndex+1;
      $this->name = $template->getName();
      if(empty($template->parent))
        $this->path = '/'.$this->name;
      else
        $this->path = $template->parent->path.'/'.$this->name;
      $this->tempalteCache = $template->tempalteCache;
      $templateStr= '';
    } else {

      $this->first = $this;
    }


    parent::__construct($templateStr, $cache);

    if($template instanceof CascadedTemplate){
      if(empty($this->cache))
        $this->cache = $template->getCache();
    }else {
      $tplCache = $this->genCache($templateStr);
      $this->setTemplateFromCache($tplCache);
    }
  }

  /**
   * This is called from a block with parent as parameter to set variables
   * @param CascadedTemplate $parent
   */
  protected function connectBlockToParent(CascadedTemplate $parent = null) {
    $this->parent = $parent;
    if(!empty($parent))
      $this->processors = &$parent->processors;
  }

  private static function getNextId() {
    static $nextId = 1;
    return $nextId++;
  }

  /**
   * @return TemplateConfig
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * @param TemplateConfig $config
   */
  public function setConfig($config) {
    $this->config = $config;
  }

  /**
   * @return string Raw template
   */
  public function getTemplate() {
    if($this->first !== $this) // not the first
      return $this->first->template;
    return $this->template;
  }

  /**
   * @param string $tpl
   */
  function setTemplate($tpl){
    $cache = $this->template = $this->genCache($tpl);
    $this->setTemplateFromCache($cache);
  }

  private function getLastRow() {
    $lastRow = $this;
    while(true) {
      $next = $this->getNextRow();
      if(empty($next))
        break;
      $lastRow = $next;
    }
    return $lastRow;
  }

  /**
   * Copies the blocks from
   * @param CascadedTemplate $parentBlock
   */
  private function copyBlocks(CascadedTemplate $parentBlock) {
    foreach($parentBlock->vars as $blockVarName=> $varValue) {
      /** @var CascadedTemplate $varValue */
      if(substr($blockVarName, 0, 6) != 'block:')
        continue;

      $firstBlock = $varValue->getFirstRow();

      $block = $this->vars[$blockVarName] = new CascadedTemplate($firstBlock);
      $block->hide();
      $block->first = $block; // block is copied from previous parent. If it's a second's row cell it's copied from the
      // first row only when it's first cell. So we set it as first.
      $block->parent = $this;
      $block->template = $varValue->getTemplate();
      $block->copyBlocks($firstBlock);
    }
  }

  /**
   * Inserts a row after the current one. If
   *
   * @param array $vars
   * @return CascadedTemplate
   * @see appendRow()
   */
  public function insertRow($vars = null){
    $className = get_class($this->first);
    /** @var CascadedTemplate $nextRow */
    $nextRow = new $className($this->first);
    $nextRow->connectBlockToParent($this->first->parent);
    //$nextRow->parent = $this->first->parent;
    $nextRow->next = $this->next;
    $this->next = $nextRow;
    $nextRow->copyBlocks($this->first);

    if(!empty($vars))
      $nextRow->setVars($vars);

    return $nextRow;
  }

  /**
   * First time it's called this method just sets vars for current row. Every next call creates a row and fills the
   * vars. After the first call it doesn't matter from which row it's called - it always creates row next to previous
   * result of this function. So you can call it like this:
   * <pre>
   *   $row->processNextRow($vars1)->processNextRow($vars2);
   * </pre>
   * or like this:
   * <pre>
   *   $row->processNextRow($vars1);
   *   $row->processNextRow($vars2);
   * </pre>
   * ... it'll have the same result
   *
   * In order to create a row after a specific row (i.e. control the order) use createNextRow() - like:
   * <pre>
   *   $row3 = $row2->createNextRow($vars3);
   *   $row4 = $row3->createNextRow($vars4);
   * </pre>
   *
   * @param array|null $vars Vars to full for the row created. Optional.
   * @return CascadedTemplate
   * @see insertRow()
   */
  public function appendRow($vars = null) {
    $processingRow = &$this->first->processingRow;

    if(empty($processingRow)) {
      // this is the first row
      $processingRow = $this;
    }else {
      $processingRow = $processingRow->insertRow();
    }

    if(!empty($vars))
      $processingRow->setVars($vars);

    $processingRow->setVisible(true);

    return $processingRow;
  }

  /**
   * @return CascadedTemplate First template in linked list (first row)
   */
  public function getFirstRow() {
    return $this->first;
  }

  /**
   * @return CascadedTemplate next template in linked list (next row)
   */
  public function getNextRow() {
    return $this->next;
  }

  public function clearRows() {
    $this->first = $this;
    $this->next = null;
    $this->first->processingRow = null;
  }

  public function reset() {
    $this->clearRows();
    $this->clearVars();
  }

  /**
   * This method should only be used from CascadedTemplate class (cross-object).
   * @param CascadedTemplate|null $next NOT optional! The default value is only to allow null value
   */
  private function _setNext(CascadedTemplate $next = null) {
    $this->next = $next;
  }

  /**
   * @return CascadedTemplate
   */
  public function getParent() {
    return $this->parent;
  }
  /**
   * @param CascadedTemplate $parent
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }
  /**
   * @param string $name
   */
  private function setName($name) {
    $this->path = $name;
    if(!empty($this->parent))
      $this->path = $this->parent->path.'/'.$name;
    $this->name = $name;
  }

  /**
   * @return boolean
   */
  public function isVisible() {
    return $this->visible;
  }
  /**
   * @param boolean $visible
   */
  public function setVisible($visible) {
    $this->visible = $visible;
    /*
    if($visible) {
      echo "showing ".$this->name.PHP_EOL;
    }else {
      echo "hiding ".$this->name.PHP_EOL;
    } */
  }
  public function show() {
    $this->setVisible(true);
  }
  public function hide() {
    $this->setVisible(false);
  }

  public function loadFromFile($filename) {
    $fullPath = $this->filePathProcessor->getFilePath($filename);
//    if (!file_exists($filename))
//      throw new \Exception('template file not found: '.$fullPath);
    $content = file_get_contents($fullPath);
    $tplCache = $this->genCache($content);
    $this->setTemplateFromCache($tplCache);
  }

  protected function setTemplateFromCache(TemplateCache $cache){
    $this->tempalteCache = $cache;
    $this->name = $cache->getName();
    $this->template = $cache->getTemplate();
    $this->setBlocksFromCache($cache->getBlocks());
  }

  public function setBlocksFromCache(array $blocks){
    foreach($blocks as $blockCache){
      /** @var TemplateCache $blockCache */
      if(!($blockCache instanceof TemplateCache))
        throw new InvalidArgumentException('invalid template cache type '.gettype($blockCache).' in sub-blocks of '.$this->name.' template.');
      $class = get_class($this);


      /** @var CascadedTemplate $block */
      $block = new $class($blockCache->getTemplate()); //, $this->cache
      $block->hide();
      $block->setName($blockCache->getName());
      $block->connectBlockToParent($this);

      $subBlocks = $blockCache->getBlocks();
      if(!empty($subBlocks))
        $block->setBlocksFromCache($subBlocks);
      $this->setVar('block:'.$blockCache->getName(), $block);
      //$this->blocks[$blockCache->getName()] = $block;
    }
  }

  /**
   * Creates TemplateCache from raw template
   * @param string $template
   * @return TemplateCache
   */
  protected function genCache($template){
    $parsedTemplate = $this->parseBlock('<block main>'.$template.'</block>');
    $tplCache = new TemplateCache('main', $parsedTemplate['blockTemplate']);

    $this->createSubBlocks($tplCache, $parsedTemplate['subBlocks']);
    unset($parsedTemplate);
    gc_collect_cycles();
    return $tplCache;
  }

  /**
   * Creates sub blocks objects
   *
   * @param TemplateCache $parent
   * @param array $subBlocks
   */
  protected function createSubBlocks(TemplateCache $parent, array $subBlocks){
    $parent->setBlocks(array());
    foreach($subBlocks as $item){
      $block = new TemplateCache($item['blockName'], $item['blockTemplate']);
      $parent->addBlock($block);
      if(count($item['subBlocks'])>0)
        $this->createSubBlocks($block, $item['subBlocks']);
    }
  }

  /**
   * Finds a block in the template, replaces it with a {%block:blockname} var and returns block and subblocks details
   * @param string $template
   * @param int $offset
   * @return array|bool returns false if no block was found or array of block details with following keys:
   *    'template' - the template after replacing the block with variable
   *    'nextOffset' - the offset to search for more blocks
   *    'blockName' - block name
   *    'blockTemplate' - the cut-out part of the whole template that represents the block.
   *    'subBlocks' - array the inner blocks returned recursively. Inner block is also returned by this function, so it
   *        has the same structure
   */
  protected function parseBlock($template, $offset = 0){
    $tag = $this->findNextTag($template, $offset);
    if($tag['tagName'] == '/block'){
      return false;
    }
    $subBlocks = array();
    //$tag is begin tag ... now find end tag.
    $nextTag = $this->findNextTag($template, $tag['nextOffset']);
    while($nextTag['tagName'] == 'block'){
      //recursively parse inner block
      $subBlock = $this->parseBlock($template, $nextTag['offset']);
      $template = $subBlock['template'];
      //$class = get_class($this);
      $subBlocks[$subBlock['blockName']] = $subBlock;
      $nextTag = $this->findNextTag($template, $subBlock['nextOffset']);

    }
    //$nextTag is the endTag
    assert($nextTag['tagName'] == '/block');
    $endTag = $nextTag;

    //we have begin and end and also all inner blocks
    $blockVar = '{%block:'.$tag['blockName'].'}';
    $newTemplate = substr($template, 0, $tag['offset']).
      $blockVar.
      substr($template, $endTag['nextOffset']);

    $start = $tag['offset'] + strlen($tag['tag']);
    $end = $endTag['offset'] - $start;
    $blockTemplate = substr($template, $start, $end);
    return array(
      'template' => $newTemplate,
      'nextOffset' => $tag['offset'] + strlen($blockVar),  // ToDo: is this the same as $tag['nextOffset']
      'blockName'=> $tag['blockName'],
      'blockTemplate' => $blockTemplate,
      'subBlocks' => $subBlocks,
    );
  }

  /**
   * Find a block tag.
   *
   * @param string $template The raw template string
   * @param int $offset offset in $template to start searching from
   * @return array The resulting array has following fields:
   *    tag - the full tag - ex '<block blockName>', empty string if not found
   *    tagName - tag name like 'block' or '/block'
   *    offset - offset where the tag is found at. Includes '<'
   *    nextOffset - where to start to look for next tag (offset + length of tag)
   *    blockName - the name of the block.
   */
  protected function findNextTag($template, $offset = 0){
    $n = preg_match('/<(block)\\s+([a-zA-Z0-9_-]+)>|<(\\/block)>/', $template, $m, PREG_OFFSET_CAPTURE, $offset);
    if($n == 0){
      //simulate end tag
      return array(
        'tag' => '',
        'tagName' => '/block',
        'offset' => strlen($template),
        'nextOffset' => strlen($template),
        'blockName' => false,
      );
    }
    if($m[1][0] == 'block'){
      $tag = $m[0][0];
      $tagOffset = $m[0][1];
      $tagName = 'block';
      $blockName = $m[2][0];
    }else if($m[3][0] == '/block'){
      $tag = $m[0][0];
      $tagOffset = $m[0][1];
      $tagName = '/block';
      $blockName = false;
    }else{
      // @codeCoverageIgnoreStart
      trigger_error('This should be unreachable code!!! something is really wrong!', E_USER_ERROR);
      return false;
      // @codeCoverageIgnoreEnd
    }

    $result = array(
      'tag' => $tag,
      'tagName' => $tagName,
      'offset' => $tagOffset,
      'blockName' => $blockName,
      'nextOffset' => $tagOffset+strlen($tag),
    );
    return $result;
  }

  /**
   *
   * @param $varName
   * @return bool|string|DisplayObject Returns variable string or Display object. If value of varName is null or
   *         not set returns false
   */
  protected function getVarOrBlock($varName) {
    // if null or not set
    if(!isset($this->vars[$varName])) {
      return false;
    }
    if(is_null($this->vars[$varName])) {
      return '';
    }
    return $this->vars[$varName];
  }

  /**
   * Returns value of a variable (always string).
   * If value of a variable is an Template object it returns $obj->getParsed()
   *
   * @ToDo: Is this method needed?
   *
   * @param $varName
   * @return string
   */
  protected function getVarAsString($varName) {
    $obj = $this->getVarOrBlock($varName);
    if($obj instanceof CascadedTemplate){
      /** @var CascadedTemplate $obj */
      return $obj->getParsed();
    }
    /** @var string $obj */
    return $obj;
  }

  public function hasVar($name) {
    return array_key_exists($name, $this->vars);
  }

  public function hasBlock($name) {
    return $this->first->hasVar('block:'.$name);
  }

  /**
   * @param string $name the name of the block
   * @return CascadedTemplate|bool
   */
  public function getBlock($name) {

    if(!$this->hasBlock($name))
      return false;

    $blockVarName = 'block:'.$name;

    // copy the block when it's first accessed
    // ToDo: maybe it's better to copy the blocks at creation time. Yes, probably is. Doing that in insertRow()
    /*
    if(empty($this->vars[$blockVarName])) {
      $theVeryFirstBlock = $this->first->getBlock($name);
      $block = $this->vars[$blockVarName] = new CascadedTemplate($theVeryFirstBlock);
      $block->first = $block; // block is copied from previous parent. If it's a second's row cell it's copied from the
                              // first row only when it's first cell. So we set it as first.
      $block->parent = $this;
      $block->template = $theVeryFirstBlock->getTemplate();
    } */

    $this->vars[$blockVarName];

    $block = $this->vars[$blockVarName];
    if(!($block instanceof DisplayObject)) {
      // @codeCoverageIgnoreStart
      trigger_error('This code should be unreachable. $block should always be instance of DisplayObject', E_USER_WARNING);
    }
    // @codeCoverageIgnoreEnd

    return $block;
  }


  /**
   *
   * @param string $varName Variable to fetch
   * @return string
   */
  public function getVar($varName) {
    if(!$this->hasVar($varName))
      return '';

    $obj = $this->vars[$varName];

    if($obj instanceof CascadedTemplate){
      /** @var CascadedTemplate $obj */
      // ToDo: Changed this to return string. Make sure to replace getVar with getVarAsString wherever string is needed
      return $obj->getParsed();
      //return $obj;
    }

    // ToDo: make sure this is impossible and remove the check
    if(!is_string($obj)) {
      throw new InvalidArgumentException('var "'.$varName.'" is "'.gettype($varName).'". It should return string or CascadedTemplate.');
    }

    /** @var string $obj */
    return $obj;
  }

  /**
   * Sets template variable.
   *
   * Note: If no variables were added before the template will be set to visible. If you want to hide a template
   *   do it after you've set variables (if you really want to set variables of a template that's not being
   *   displayed).
   *
   * @param string $varName
   * @param string|DisplayObject $value
   */
  public function setVar($varName, $value) {
    if(empty($this->vars)) {
      $this->show();
    }

    $this->checkVarValue($value);
    if($value instanceof CascadedTemplate)
      $value->setParent($this);

    $this->vars[$varName] = $value;
  }

  public function checkVarValue($varValue) {
    if(is_object($varValue)) {
      if(!($varValue instanceof DisplayObject)) {
        throw new InvalidArgumentException('if $value is object it must be instance of DisplayObject');
      }
    }
  }

  public function clearVars() {
    foreach($this->vars as $varName=>$varValue) {
      if(substr($varName, 0, 6) != 'block:') {
        unset($this->vars[$varName]);
      }
    }
  }

  /**
   * Set template vars. If there are any vars set prior call of this function they are REMOVED.
   *
   * @param array $vars
   */
  public function setVars(array $vars) {
    if(empty($this->vars)) {
      $this->show();
    }

    $this->clearVars();

    foreach($vars as $varName=>$varValue) {
      $this->checkVarValue($varValue);
      $this->vars[$varName] = $varValue;
    }
    //$this->vars = $vars;
  }

  /**
   * Adds more vars to the template. Does not change existing ones, unless a var with the same name is in $vars param.
   * @param array $vars
   */
  public function addVars(array $vars) {
    foreach($vars as $varName=>$varValue) {
      $this->checkVarValue($varValue);
      $this->setVar($varName, $varValue);
    }
  }

  public function getReplaceValue($varName, $varOnly = false) {
    $value = $this->getVarOrBlock($varName);

    //replace blocks
    if(!$varOnly) {
      if($value instanceof DisplayObject) {
        /** @var DisplayObject $value */
        return $value->getHtml();
      }
    }


    /*
    if(substr($varName, 0, 8) == 'fileUrl:') {
      throw new \RuntimeException('To Do: {%fileurl:path/to/file.html} ');
    }*/

    /*
      //html escaping is implemented as a VarProcessor
    $quoted = false;
    $firstTwo = substr($varName, 0, 2);
    if($firstTwo == 'q:' || $firstTwo == 'q_'){ // q_ is for backwards compatibility
      $varName = substr($varName, 2);
      $quoted = true;
    }
    */

    if($value === false)
      $value = $this->runProcessorsOnMissing($varName, $this);

    if($value !== false)
      $value = $this->runProcessorsOnExisting($varName, $value);

    if($value === false){
      // first try to get it from parent
      if(!is_null($this->parent)){
        if(substr($varName, 0, 6) != 'block:'){

          $value = $this->parent->getReplaceValue($varName, true);
          return $value;
        }
      }

      /*
      // language is implemented as VarProcessor
      // then test for language variable
      if(preg_match('/^(q?)(l[fwul]?)_(.*)$/', $varName, $m)){
        $value = $this->getLangVar($m[3], $m[2]);
        // 'q' for quotted
        if($m[1] == 'q')
          $quotted = true;


        if($quotted){
          //$value = str_replace(array('   ', '  '), array('&nbsp; ', '&nbsp; &nbsp;'), $value);
          return str_replace(array('   ', '  '), array('&nbsp; ', '&nbsp; &nbsp;'), nl2br(htmlSpecialChars($value, ENT_QUOTES)));
        }else{
          return $value;
        }
      }
      */
      if($this->config->reportUndefinedVariables)
        throw new \RuntimeException('Variable "'.$varName.'" is undefined');

      return '';
    }
    return $value;
  }

  protected function replaceCallback($matches) {
    $varName = $matches[1];
    $value = $this->getReplaceValue($varName);
    return $value;
  }

  protected function parseVars() {
    /*
      {%varName}
      {%block:blockName}
      {%fileurl:path/to/file.html}
     */
    $patterns = [
      '@\\{%([a-z0-9\\/\\.:_-]+[^\\}]*)\\}@i', ///    a-z0-9 :_- .....
      //'@\\{%([a-z0-9:\\/\\._-]+)\\}@i',
    ];
    //'@\\{%([a-z0-9:\\/\\._-]+)\\}@i',
    $result = preg_replace_callback($patterns, array($this, 'replaceCallback'), $this->getTemplate());//, -1, $count
    return $result;
  }

  public function getParsed($alwaysVisible = false) {

    if(!$this->visible && !$alwaysVisible)
      return '';


    // ToDo: if vars are not filled and not alwaysVisible,  and it is a block -> hide by default

    $parsed = $this->parseVars();

    $saveProcessingRow = &$this->processingRow;

    // for less stack usage (Be nice to the stack. He is your friend. )
    if($this === $this->first) {
      /** @var CascadedTemplate $next */
      $next = $this;
      while(!is_null($next = $next->getNextRow())) {
        $parsed .= $next->getParsed();
      }
    }

    $this->processingRow = &$saveProcessingRow;


    return $parsed;
  }

  /**
   * @codeCoverageIgnore
   */
  public function dumpVars() {
    echo "var count: ".count($this->vars).PHP_EOL;
    foreach($this->vars as $name=>$var) {
      if(gettype($var) == 'object')
        echo "$name: ".get_class($var).PHP_EOL;
      else
        echo "$name: ".$var.PHP_EOL;
    }
  }

}

CascadedTemplate::setDefaultConfig(new TemplateConfig());
