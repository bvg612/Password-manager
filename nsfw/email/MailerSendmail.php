<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 04-02-18
 * Time: 12:45 PM
 */

namespace nsfw\email;


class Mailer extends AbstractMailer {

  public function send(Message $message) {
    // TODO: Implement send() method.
  }

  public function sendSendmail(Message $message) {
    mail($message->recipients->getCommaSeparated('to'), $message->getSubjectEncoded(), $message->getBody());
  }

}
