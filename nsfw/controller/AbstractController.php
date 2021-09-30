<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 22-04-16
 * Time: 9:11 AM
 */

namespace nsfw\controller;


use Exception;
use nsfw\exception\ActionNotFoundException;

abstract class AbstractController implements PageController{
  public $debug = false;
  /** @var string */
  protected $actionDir = false;
  protected $errorReporter;

  abstract public function runActions();

  /**
   * @param $actionPath
   * @param $action
   * @param bool $enforce
   * @return Action
   * @throws ActionNotFoundException
   * @throws Exception
   */
  public function loadAction($actionPath, $action, $enforce = false) {

    /*
    echo "<pre>";
    echo "actionDir {$this->actionDir}".PHP_EOL;
    echo "actionPath: $actionPath".PHP_EOL;
    echo "action: $action".PHP_EOL;
    echo "</pre>";*/

    $actionFile = $this->actionDir;

    //$actionFile .= $actionPath;

    $actionPlusPath = $actionPath.'/'.$action;

    $actionFile .= $actionPlusPath.'.php';

    if($this->debug)
      echo 'enforce: '.var_export($enforce, true).", found: ".(file_exists($actionFile)?'YES':'NO').", Action ".$action.", file: ".$actionFile."<br />\n";

    if(!file_exists($actionFile)) {
      if(!$enforce){
        return new NullAction();
      } else {
        throw new ActionNotFoundException($actionPlusPath, $actionFile);
      }
    }

    \safeInclude($actionFile);
    $actionClass = basename($this->actionDir).str_replace('/', '\\', $actionPlusPath);
    if(!class_exists($actionClass)){
      throw new ActionNotFoundException($actionPlusPath, $actionFile, 'Class not found for "'.$actionClass.'" in file "'.$actionFile.'"');
    }

    if(!array_key_exists('nsfw\controller\Action', class_implements($actionClass, false))){
      throw new Exception("class ".$actionClass." does not implement Action interface");
    }

    $actionObj = new $actionClass();
    return $actionObj;
  }
}
