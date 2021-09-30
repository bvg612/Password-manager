<?php
/**
 * User: npelov
 * Date: 22-06-17
 * Time: 6:43 PM
 */

namespace nsfw\controller;


/**
 * Extend this class when you want to implement an action to handle all the subactions. You must define runEnd() method
 * and return true whan page is found and false when page is not found
 *
 * Class ControllerAction
 * @package nsfw\controller
 */
abstract class ControllerAction extends AbstractAction {
  /** @var bool Set this to false in prepare method if you want the parent controller to handle the action */
  protected $isController = true;


  public function __construct() {
    parent::__construct();
    $this->lastActionOnly = false;
  }

  public function getActionPaths() {
    $max = $this->maxActionLevel;
    $path = '';
    $actionPaths = [];
    for($i=$this->actionLevel+1; $i<=$max; ++$i) {
      $path .= '/' . $this->context['actions'][$i];
      $actionPaths[] = $path;
    }
    return $actionPaths;
  }

  /**
   * If this method exists and returns true the action processing stops here. No more action will be loaded!!! This is
   * used when an action needs to work as controller and handle all subactions. For example if the action is defined in
   * external library.
   * @return bool
   */
  public function isController() {
    return $this->isController;
  }


  public function run() {

    $this->runMiddle();

    return $this->runEnd();
  }


}
