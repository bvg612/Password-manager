<?php

namespace nsfw\controller;


/**
 * Interface Action
 * @package nsfw\controller
 * [method prepare()] (optional) Prepare the template. At least one of the actions should load main template in it's prepare() method.
 */
interface Action {

  /**
   * @return mixed True if this action will be run only if it's last (end point) action. False if it'll be run even
   * if it's middle point in action path
   */
  public function lastActionOnly();

  /**
   * @param array $context
   */
  public function setContext(array $context);

  public function addContext(array $newContext, array $filterKeys);

  /**
   * @return array
   */
  public function getContext();

  /**
   * Prepare the template. At least one of the actions should load main template in it's prepare() method.
   * Only called if exists.
   */
  //public function prepare();

  /**
   * Actually run the action.
   *
   * Should not change template
   * @return bool True if page is found, false if not found
   */
  public function run();

  // obtional abstract methods - if you implement them they will be run
  //function prepare();
  //function postAction();
}
