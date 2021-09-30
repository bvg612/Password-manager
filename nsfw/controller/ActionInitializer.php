<?php
/**
 * User: npelov
 * Date: 13-05-17
 * Time: 1:55 PM
 */

namespace nsfw\controller;


abstract class ActionInitializer extends AbstractAction {
  /**
   * Initialization of non-excluded paths
   */
  abstract public function init();

  /**
   * Initialization of all paths
   */
  abstract public function initCommon();

  /**
   * The beginning of paths for which this init() won't be run. The paths must include leading slash.
   *
   * @return array
   */
  abstract protected function getExcludePaths();

  /**
   * @param string $path
   * @return bool
   */
  public function isPathExcluded($path) {
    $paths = $this->getExcludePaths();
    foreach($paths as $excludedPath) {
      $len = strlen($excludedPath);
      if(substr($path, 0, $len) == $excludedPath)
        return true;
    }
    return false;
  }

  public function prepare() {
    $this->init();
  }

  public function runMiddle() {
  }

  function runEnd() {
  }

}
