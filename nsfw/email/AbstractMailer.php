<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 04-02-18
 * Time: 11:43 AM
 */

namespace nsfw\email;


abstract class AbstractMailer {

  abstract public function send(Message $message);

}
