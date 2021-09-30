<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 12:01 PM
 */

namespace nsfw\Events;


abstract class Listener {
  /**
   * @param Event $event
   */
  abstract public function run(Event $event);

  /**
   * @return string
   */
  abstract public function getType();
}
