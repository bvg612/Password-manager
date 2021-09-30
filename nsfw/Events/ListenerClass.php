<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 11:58 AM
 */

namespace nsfw\Events;


class ListenerClass extends Listener{
  /** @var string */
  public $className;
  /** @var string */
  public $method;
  /** @var object|null */
  public $obj = null;

  /**
   * ListenerClass constructor.
   * @param string $className
   * @param string $method
   */
  public function __construct($className, $method) {
    $this->className = $className;
    $this->method = $method;
  }

  public function getType() {
    return 'class';
  }

  public function run(Event $event) {
    $className = $this->className;
    if(empty($this->obj))
      $this->obj = new $className();
    $callback = array($this->obj, $this->method);
    call_user_func($callback, $event);
  }


}
