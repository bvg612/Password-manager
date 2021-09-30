<?php

namespace nsfw\controller;


use Exception;
use nsfw\errors\iErrorReporter;
use nsfw\errors\ErrorReporter;
use nsfw\forms\AbstractForm;
use nsfw\template\CascadedTemplate;
use nsfw\template\DisplayObject;

/**
 * Class AbstractAction
 *
 * This class is the usual parent of actions
 *
 * @package nsfw\controller
 * @method prepare() (optional) Prepare the template. At least one of the actions should load main template
 *   in it's prepare() method. Returns true if no more actions should be processed
 *
 * @property PageControllerUrl $pageController
 * @property int $actionRedirectPass
 * @property array $langActions
 * @property int $maxActionLevel
 * @property array $actions
 * @property int $actionLevel
 * @property int $prevLevelAction
 * @property bool $isEndAction
 * @property ErrorReporter $errorReporter
 */
abstract class AbstractAction implements Action{
  protected $pageFound = false;

  /** @var array $context is the "global" variables passed to the action by the controller */
  protected $context = [];
  /**
   * @var bool True if this action will be run only if it's last (end poinxxxxxt) action. False if it'll be run even if it's
   * middle point in action path
   */
  protected $lastActionOnly = true;

  /**
   * AbstractAction constructor.
   */
  public function __construct() {
  }


  /**
   * @param bool|null $lastActionOnly
   * @return bool
   */
  public function lastActionOnly($lastActionOnly = null) {
    if(!is_null($lastActionOnly))
      $this->lastActionOnly = $lastActionOnly;
    return $this->lastActionOnly;
  }

  /**
   * @param null|string $var
   * @return array
   * @throws Exception
   */
  public function getContext($var = null) {
    if(!empty($var)) {
      if(!isset($this->context[$var]))
        throw new Exception('Context var '.$var.' is not set');
      return $this->context[$var];
    }
    return $this->context;
  }

  public function setContext(array $context) {
    $this->context = $context;
  }

  public function addContext(array $newContext, array $filterKeys = []) {
    $filter = array_fill_keys($filterKeys, true);
    foreach($newContext as $key => $value) {
      if(empty($filter[$key])) {
        $this->context[$key] = $newContext[$key];
      }
    }

  }

  public function __get($name) {
    if(!array_key_exists($name, $this->context))
      throw new Exception('Magic property ('.__CLASS__.')->'.$name.' not found');
    return $this->context[$name];
  }

  public function __isset($name) {
    return array_key_exists($name, $this->context);
  }

  public function __set($name, $value) {
    if(array_key_exists($name, $this->context))
      throw new Exception('Magic property ('.__CLASS__.')->'.$name.' is read only');

    throw new Exception('Magic property ('.__CLASS__.')->'.$name.' not found');
  }

  /**
   * @param string $varName
   * @param string|DisplayObject $value
   */
  public function setMainTplVar($varName, $value) {
    $this->pageController->getTemplate()->setVar($varName, $value);
  }

  /**
   * @param string $mainTemplateFile
   * @return CascadedTemplate
   */
  public function createMainTemplate($mainTemplateFile) {
    $mainTpl = new CascadedTemplate();
    $mainTpl->loadFromFile($mainTemplateFile);
    $this->pageController->setTemplate($mainTpl);
    return $mainTpl;
  }

  /**
   * @param AbstractForm $form
   * @return CascadedTemplate
   */
  public function setCenterForm(AbstractForm $form) {
    /** @var CascadedTemplate $mainTpl */
    $mainTpl = $this->pageController->getTemplate();
    $mainTpl->setVar('center', $form);
    $tpl = $form->getTemplate();
    $tpl->setParent($mainTpl);
    return $tpl;
  }

  /**
   * @param $centerTemplateFile
   * @return CascadedTemplate
   */
  public function createCenterTemplate($centerTemplateFile){
    $mainTpl = $this->pageController->getTemplate();
    $tpl = CascadedTemplate::createFromFile($centerTemplateFile);
    $mainTpl->setVar('center', $tpl);
    return $tpl;
  }

  /**
   * Implement this method to run actions when the action is in the middle of action path. See runEnd() for more info.
   * Middle action method is also run if the action is end action. The idea is to perform actions that are common for
   * the whole group of end actions. If you don't want that behaviour check "isEndAction" property
   *
   * @see runEnd()
   */
  public function runMiddle() {
  }

  /**
   * Implement this method to run actions when it's direct call - when the action name is the last in action path:
   * <p>  <b>/action1/action2/endAction.html</b></p>
   * In example above action1 and action2 are middle actions, endAction is "end action"
   * @return bool
   */
  abstract function runEnd();

  /**
   * Usually you wouldn't want to override this method. Use runMiddle() and runEnd()
   *
   * @see runEnd()
   * @see runMiddle()
   */
  public function run() {

    $this->runMiddle();

    if($this->isEndAction) {
      $this->context['pageFound'] = true; // ToDo: We don't use this. Page is found when this function returns true
      $this->runEnd();
      return true;
    }
    return false;
  }

  /**
   * @param string $path
   * @return string
   */
  public static function pathToNamespace($path) {
    return str_replace('/', '\\', $path);
  }

  /**
   * @param string $namespace
   * @return string
   */
  public static function NamespaceToPath($namespace) {
    return str_replace('/', '\\', $namespace);
  }


}
