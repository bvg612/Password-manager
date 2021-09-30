<?php

namespace nsfw\controller;


/**
 * Class IndirectAction
 *
 * Runs As regular action, but also when the action is in the middle of action path.
 *
 * @package nsfw\controller
 */
abstract class IndirectAction extends AbstractAction{
  public function __construct() {
    $this->lastActionOnly = false;
  }

  public function runEnd() {
  }


}
