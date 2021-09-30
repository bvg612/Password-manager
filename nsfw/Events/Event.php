<?php
/**
 * User: npelov
 * Date: 21-10-17
 * Time: 11:49 AM
 */

namespace nsfw\Events;


abstract class Event {
  /**
   * @return string
   */
  abstract public function getName();
}
