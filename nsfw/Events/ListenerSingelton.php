<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 12:09 PM
 */

namespace nsfw\Events;


class ListenerSingelton extends Listener {
  /** @var string */
  public $className;
  /** @var string */
  public $method;

  /**
   * ListenerSingelton constructor.
   * @param string $className
   * @param string $method
   */
  public function __construct($className, $method) {
    $this->className = $className;
    $this->method = $method;
  }


  public function getType() {
    return 'singelton';
  }

  public function run(Event $event) {
    $className = $this->className;
    /** @noinspection PhpUndefinedMethodInspection */
    $callable = [$className::getInstance(), $this->method];
    $result = call_user_func($callable, $event);
  }

}
