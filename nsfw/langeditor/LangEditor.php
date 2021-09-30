<?php
/**
 * User: npelov
 * Date: 22-06-17
 * Time: 7:23 PM
 */

namespace nsfw\langeditor;


use nsfw\controller\AbstractAction;
use nsfw\controller\ControllerAction;
use nsfw\database\Database;
use nsfw\database\XmlImporter;
use nsfw\i18\DbLanguage;
use nsfw\session\Session;
use nsfw\template\HtmlEscapeProcessor;
use nsfw\template\UrlEncodeProcessor;

class LangEditor extends ControllerAction {
  /** @var string set this in inheritor */
  protected $loginUrl;

  /** @var string set this in inheritor */
  protected $siteUrl;

  /** @var string set this in inheritor */
  protected $langEditorUrl;

  /** @var Database */
  protected $db;

  /** @var Session */
  protected $session;

  /** @var string this or tplWebPath must be set by inheritor if nsfw base dir is not visible on web (to a link that's visible on web) */
  protected $projectDir = false;
  /** @var string|false See above*/
  protected $tplWebPath = false;
  /** @var string this must be set by inheritor if nsfw base dir is not visible on web (to a link that's visible on web) */
  protected $tplRootDir = false;

  protected $langActions = [];

  public function __construct() {
    $this->tplRootDir = NSFW_BASE_DIR.'/langeditor/tpl';
    parent::__construct();
    self::createTables($this->db);
    DbLanguage::getInstance($this->db);
    Template::initDefaultHtmlProcessors(true); // ToDo: set default processors
    //Template::addDefaultProcessor(new UrlEncodeProcessor());
    //Template::addDefaultProcessor(new HtmlEscapeProcessor());

  }

  public static function createTables($db) {
    $xi = new XmlImporter($db);
    $xi->import(NSFW_BASE_DIR . '/data/db/lang_codes.xml');
    $xi->import(NSFW_BASE_DIR . '/data/db/i18.xml');
  }

  protected function setTemplatePaths() {

    if(!empty($this->projectDir)) {
      if(empty($this->tplWebPath)) {
        $projectDirLen = strlen($this->projectDir);
        if(substr($this->tplRootDir, 0, $projectDirLen) == $this->projectDir) {

          $this->tplWebPath = substr($this->tplRootDir, $projectDirLen + 1);
        }
      }
    }

    //var_dump($this->tplRootDir, $this->tplWebPath);exit;
    $config = Template::getDefaultConfig();
    $config->setTplPath($this->tplRootDir, $this->tplWebPath);
    $config->mainDir = $this->tplRootDir;
    $config->subtemplateDir = false;
  }

  protected function loadAction($fullActionPath) {
    $namespace = self::pathToNamespace($fullActionPath);
    \safeInclude($fullActionPath);

  }

  /**
   * Override this in inheritor
   * @return bool
   */
  public function isLogged() {
    return false;
  }

  public function setMenu(){}

  public function prepare() {
    $this->setTemplatePaths();

    if(!$this->isLogged()) {
      httpRedirect($this->loginUrl);
    }

    $mainTpl = new Template();
    $mainTpl->loadFromFile('index.html');
    $mainTpl->siteUrl = $this->siteUrl;
    $mainTpl->tplWebPath = $this->tplWebPath;
    $mainTpl->langEditorUrl = $this->langEditorUrl;
    $this->pageController->setTemplate($mainTpl);

    $actionPaths = $this->getActionPaths();
    $context = $this->context;
    /*
    'pageController' => $this,
      'actionRedirectPass' => 0,
      'errorReporter' => $this->errorReporter,
      'actions' => $this->actions,
      'maxActionLevel' => count($this->actions)-1,
      'actionPath' => '',
      'action' => '/initialize',
      'isEndAction' => false,
      'prevLevelAction' => '',
      */
    end($actionPaths);
    $lastActionPathIndex = key($actionPaths);
    $context['langEditor'] = $this;
    foreach($actionPaths as $index => $actionPath) {
      $context['isEndAction'] = $lastActionPathIndex == $index;
      $context['db'] = $this->db;
      $context['session'] = $this->session;
      $context['mainTpl'] = $mainTpl;
      $context['langEditorUrl'] = $this->langEditorUrl;
      $fullActionPath = 'nsfw/langeditor/action'.$actionPath;
      $className = self::pathToNamespace($fullActionPath);
      if(substr($className, -1, 1) == '\\')
        $className .= 'index';
      $action = $this->langActions[$fullActionPath] = new $className();
      /** @var AbstractAction $action */
      $action->setContext($context);
    }
  }


  function runEnd() {
    $pageFound = false;
    foreach($this->langActions as $action) {
      /** @var AbstractAction $action */
      $pageFound = $pageFound || $action->run();
    }
    return $pageFound;
  }

}
