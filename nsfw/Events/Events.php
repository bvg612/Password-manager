<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 11:46 AM
 */

namespace nsfw\Events;


use nsfw\Singleton;

/**
 * Class Events
 * @package nsfw\Events
 *
 * @method static Events getInstance();
 */
class Events extends Singleton {
  protected $listeners = [];

  /**
   * @param $eventName
   * @param Listener $listener
   * @throws \Exception
   */
  public function listen($eventName, Listener $listener) {
    if(empty($this->listeners[$eventName]))
      throw new \Exception('Event '.$eventName.' does not exists');
    $type = $listener->getType();
    switch($type) {
      case 'class':
        $this->listeners[$eventName][] = $listener;
    }
  }

  public function dispatch(Event $event) {
    $name = $event->getName();
    if(empty($this->listeners[$name]))
      return;
    foreach($this->listeners[$name] as $listener) {
      /** @var Listener $listener */
      $listener->run($event);
    }
  }
}
