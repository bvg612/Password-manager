<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 22-04-16
 * Time: 9:14 AM
 */

namespace nsfw\controller;


use Exception;
use nsfw\Config;
use nsfw\errors\ErrorReporter;
use nsfw\errors\NullErrorReporter;
use nsfw\exception\ActionNotFoundException;
use nsfw\exception\UserException;
use nsfw\template\CascadedTemplate;
use nsfw\template\Template;

/**
 * Class PageControllerUrl
 * <p/>
 *
 * @ToDo: Decide what to do with template create problem.
 * <p>Template create problem: It is a problem to create template upfront, because some of the actions (or pre- or
 * parent actions) may decide which template to use. Generally Templates are two - when logged in and front.<br />
 * </p>
 *
 *
 * @package nsfw\controller
 */
class PageControllerUrl extends AbstractController{

  /** @var boolean */
  public $actionRedirect = false;

  protected $pageFound = false;

  /** @var string */
  protected $defaultAction = 'index';

  /** @var array */
  protected $orgActions = [];

  /** @var array */
  protected $actions = [];

  protected $actionObjects = [];

  /** @var string */
  protected $url;

  /** @var Template */
  protected $tpl;

  protected $pageBlocks = [];

  /** @var MultiBlock */
  protected $pageHead;

  protected $globals = [];

  /** @var ErrorReporter */
  protected $errorReporter;

  /**
   * @var array variables that will not be preserved between actions. That's usually the ones that are set for every
   * action, so there is no point to filter them, but we do just in case we decide to pass every variable as
   * a reference.
   */
  protected $filterKeys = [];

  /**
   * PageControllerUrl constructor.
   * @param string $actionDir
   * @param string $url Optional
   * @throws Exception
   */
  public function __construct($actionDir, $url = null) {
    $this->errorReporter = NullErrorReporter::getInstance();
    if(is_null($url)) {
      if(empty($_SERVER['REQUEST_URI'])) {
        throw new Exception('Cannot determine server uri. CLI?');
      }
      $url = $_SERVER['REQUEST_URI'];
      $webPath = Config::getInstance()->webPath;
      $len = strlen($webPath);
      if(substr($url, 0, $len) != $webPath) {
        throw new Exception('url "'.$url.'" is not under web path, specified in config ('.$webPath.')');
      }
      $url = substr($url, $len);
    }
    if(substr($url, 0, 1) !== '/')
      $url = '/'.$url;
    $this->url = $url;
    $this->actionDir = $actionDir;
    $ph = $this->pageHead = new PageHeadBlock();
    //$ph->addBlock('htmlHead', $ph);
    $this->addPageBlock('pageInfo', new PageInfoBlock());
    $this->addPageBlock('htmlHead', $ph);
  }

  /**
   * @return ErrorReporter
   */
  public function getErrorReporter() {
    return $this->errorReporter;
  }

  /**
   * @param ErrorReporter $errorReporter
   */
  public function setErrorReporter($errorReporter) {
    $this->errorReporter = $errorReporter;
  }

  /**
   * @param string $name
   * @param $value
   */
  public function setGlobal($name, $value) {
    $this->globals[$name] = $value;
  }

  /**
   * @param string $name
   * @return mixed
   */
  public function getGlobal($name) {
    return $this->globals[$name];
  }


  public function clearBlocks() {
    $this->pageBlocks = [];
  }

  /**
   * @param string $name
   * @param PageBlock $pageBlock
   */
  public function addPageBlock($name, PageBlock $pageBlock) {
    $this->pageBlocks[$name] = $pageBlock;
  }

  /**
   * @param string $name
   */
  public function removePageBlock($name) {
    unset($this->pageBlocks[$name]);
  }

  /**
   * @param $name
   * @return PageInfoBlock|PageHeadBlock|PageInfoBlock
   */
  public function getPageBlock($name) {
    return $this->pageBlocks[$name];
  }

  /**
   * @param string $cssFile
   * @param string|null $media
   */
  public function addCssFile($cssFile, $media = '') {
    $css = $this->pageHead->getBlock('css');
    /** @var CssFiles $css */
    $css->addFile($cssFile, ['media'=>$media]);
  }

  /**
   * @param string $jsFile
   */
  public function addJsFile($jsFile) {
    $js = $this->pageHead->getBlock('js');
    /** @var JavascriptFiles $js */
    $js->addFile($jsFile);
  }

  /**
   * @param string $title
   */
  public function setPageTitle($title) {
    $this->pageBlocks['pageInfo']->setTitle($title);
  }

  /**
   * @return Template
   */
  public function getTemplate() {
    return $this->tpl;
  }

  /**
   * @param Template $tpl
   */
  public function setTemplate(Template $tpl) {
    $this->tpl = $tpl;
  }

  /**
   * @return string
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @return array
   */
  public function getActionObjects() {
    return $this->actionObjects;
  }



  public function displayErrors(){
    $this->tpl->setVar('errors', $errorsHtml = $this->errorReporter->getHtml());
  }

  public function parseUrl($url){
    $parsed = parse_url($url);
    $pathParts = explode('/', $parsed['path']);
    array_shift($pathParts);
    $numParts = count($pathParts);
    $currentPartLevel = 0;
    //$this->actions = array('index');
    foreach($pathParts as $part){
      $currentPartLevel++;
      if($part == ''){
        if($currentPartLevel<$numParts){
          //empty string in the middle - ignore it
          continue;
        }
      }
      $this->orgActions[] = $part;
      $action = preg_replace('/[^a-zA-Z0-9\._-]+/', '', $part);
      if(preg_match('/^(.+)\.(html|js)$/i', $action, $m)){
        $action = $m[1];
      }

      $this->actions[] = $action;
    }
  }

  public function initContext() {
    return array_merge($this->globals, array(
      'pageController' => $this,
      'actionRedirectPass' => 0,
      'errorReporter' => $this->errorReporter,
      'actions' => $this->actions,
      'maxActionLevel' => count($this->actions)-1,
      'actionPath' => '',
      'action' => '/initialize',
      'isEndAction' => false,
      'prevLevelAction' => '',
    ));

  }

  protected function copyArray(array &$dest, array $src, array $filter = []) {
    $filter = array_fill_keys($filter, true);
    foreach($src as $key => $value) {
      if(empty($filter[$key])) {
        $dest[$key] = $src[$key];
      }
    }
  }

  protected function removeKeys() {
  }

  public function loadActions() {

    if(empty($this->actionDir) || !is_dir($this->actionDir))
      throw new Exception('actionDir ('.var_export($this->actionDir, true).') is not set or directory does not exist');

    $actionRedirectPass = 0;

    do{ // redirect action loop
      $this->parseUrl($this->url);
      $actionContext = $this->initContext();
      $this->actionRedirect = false;
      ++$actionRedirectPass;
      $fullActionPath = '/'.implode('/', $this->actions);
      end($this->actions); $lastActionIndex = key($this->actions);
      $actionPath = '';
      $action = 'initialize';
      $actionWithPath = $actionPath.'/'.$action;
      $actionContext['actionRedirectPass'] = $actionRedirectPass;
      $actionContext['actionLevel'] = 0;
      $actionContext['action'] = $actionWithPath;
      $actionContext['actionPath'] = $actionPath;
      $actionContext['pageFound'] = &$this->pageFound;
      $this->filterKeys = array_keys($actionContext);

      // load initializer
      /** @var ActionInitializer $actionObj */
      $actionObj = $this->loadAction('', $action, false);
      $includeGlobalsCopy = $actionContext;
      $actionObj->setContext($includeGlobalsCopy);
      $this->actionObjects[$actionWithPath] = $actionObj;
      $actionObj->initCommon();
      if(method_exists($actionObj, 'prepare') && !$actionObj->isPathExcluded($fullActionPath))
        $actionObj->prepare();

      // preserve context
      $this->copyArray($actionContext, $actionObj->getContext(), $this->filterKeys);



      $prevLevelAction = '';
      end($this->actions);
      foreach($this->actions as $level=>$action){
        $actionContext['actionLevel'] = $level;
        $isEndAction = $actionContext['isEndAction'] = $level == $lastActionIndex;

        if(empty($action))
          $action = $this->defaultAction;

        if($level > 0){
          $prevLevelAction = $this->actions[$level-1];
        }

        $actionContext['prevLevelAction'] = $prevLevelAction;
        $actionPath = '';
        for($i=0;$i<$level;++$i){
          $actionPath .= '/'.$this->actions[$i];
        }

        // make sure no one else set this
        if(isset($actionFile))
          unset($actionFile);


        $actionContext['action'] = $actionPath . '/' . $action;
        $actionObj = $this->loadAction($actionPath, $action, $isEndAction);
        if(!$isEndAction && $actionObj->lastActionOnly()) {
          $actionObj = new NullAction();
        }
        $actionObj->setContext($actionContext);

        $actionWithPath = $actionPath.'/'.$action;
        $this->actionObjects[$actionWithPath] = $actionObj;
        $actionContext['action'] = $actionWithPath;
        $actionContext['actionPath'] = $actionPath;

        /** @var Action $actionObj */
        if(method_exists($actionObj, 'prepare'))
          $actionObj->prepare();

        $this->copyArray($actionContext, $actionObj->getContext(), $this->filterKeys);
        if(is_callable([$actionObj, 'isController'])) {
          if($actionObj->isController())
            break;// no more actions
        }

      } // foreach actions
      if($actionRedirectPass > 10){
        break;
      }

    }while($this->actionRedirect);
    unset($actionContext['actionRedirectPass']);

    return $actionContext;
  }

  /**
   * @param bool $doNotReturnDisplay Will not return display (and possibly execute getHtml() method on DisplayObjects)
   * @return string
   * @throws Exception
   */
  public function runActions($doNotReturnDisplay = false) {

    $context = $this->loadActions();


      if(is_file($this->actionDir . '/pre_action.php')) {
        $returnContext = $context;
        \safeInclude($this->actionDir . '/pre_action.php', $returnContext);
        $this->copyArray($context, $returnContext, $this->filterKeys);
      }

      try {

//    foreach($this->actionObjects as $actionPath=>$actionObject){
//      /** @var Action $actionObject */
//      if(method_exists($actionObject, 'prepare'))
//        $actionObject->prepare();
//    }


      $pageFound = false;
      $actionPath = '';
      foreach($this->actionObjects as $actionPath => $actionObject) {
        /** @var Action $actionObject */
        $actionObject->addContext($context, $this->filterKeys);
        $pageFound = $pageFound || $actionObject->run();
        $this->copyArray($context, $actionObject->getContext(), $this->filterKeys);
      }
      $this->pageFound = $pageFound;
    }catch (UserException $e) {
      $this->errorReporter->addErrors($e->getMessage());
    }

      $this->runPostActions();

    if(!$this->pageFound)
      throw new ActionNotFoundException($actionPath, '-', 'Page not found. You need to return true from at least one runEnd() method');

    if(file_exists($this->actionDir .  '/post_action.php')){
      $returnContext = $context;
      \safeInclude($this->actionDir .  '/post_action.php', $returnContext);
      $this->copyArray($context, $returnContext, $this->filterKeys);
    }

    if(empty($this->tpl))
      throw new Exception('Template not set. At least one action should set template in it\'s prepare() method.');

    $this->displayErrors();

    if($doNotReturnDisplay)
      return '';

    return $this->getDisplay();
  }

  public function runPostActions() {
    // run in reverse. Normal order could be implemented by overriding run method.
    $actionObjects = array_reverse($this->actionObjects);
    foreach($actionObjects as $actionPath=>$actionObject) {
      if(method_exists($actionObject, 'postAction'))
        $actionObject->postAction();
    }
  }

  public function getDisplay() {
    foreach($this->pageBlocks as $name=>$pageBlock) {
      /** @var PageBlock $pageBlock */
      $this->tpl->setVar($name, $pageBlock);
    }
    return $this->tpl->getHtml();
  }

}
