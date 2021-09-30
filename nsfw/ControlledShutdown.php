<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 3/25/2019
 * Time: 3:16 PM
 */

namespace nsfw;

/**
 * Class ControlledShutdown
 *
 * @method static ControlledShutdown getInstance()
 */
class ControlledShutdown extends Singleton {

  /** @var bool Flag to only call shutdown functions once */
  protected $shutdownCalled = false;
  protected $callbacks = [];

  /**
   * ControlledShutdown constructor.
   */
  protected function __construct() {
    parent::__construct();
    register_shutdown_function([$this, 'shutdownFunction']);
  }


  public function registerCallback($callback, $order = 1000, array $params = []) {
    $this->callbacks[] = [
      'callback' => $callback,
      'params' => $params,
      'order' => $order,
    ];
  }

  public function shutdownFunction() {
    if($this->shutdownCalled)
      return;
    $this->shutdownCalled = true;
    ksort($this->callbacks);
    uasort($this->callbacks, function($a, $b){
      $orderA = $a['order'];
      $orderB = $b['order'];

      if($orderA == $orderB)
        return 0;
      return ($orderA < $orderB) ? -1 : 1;
    });
    foreach($this->callbacks as $callback) {
      call_user_func_array($callback['callback'], $callback['params']);
    }
  }

  public static function register($callback, $order = 1000, array $params = []) {
    static::getInstance()->registerCallback($callback, $order, $params);
  }

}
