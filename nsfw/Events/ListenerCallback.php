<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 12:16 PM
 */

namespace nsfw\Events;


class ListenerCallback extends Listener {
  private $callback = null;

  /**
   * ListenerCallback constructor.
   * @param callable $callback
   */
  public function __construct(callable $callback) {
    $this->callback = $callback;
  }

  public function run(Event $event) {
    call_user_func($this->callback, $event);

  }

  public function getType() {
    return 'callback';
  }


}
