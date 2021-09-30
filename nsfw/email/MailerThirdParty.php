<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 4/18/2018
 * Time: 5:54 PM
 */

namespace nsfw\email;

use nsfw\email\thirdparty\PHPMailer;
use nsfw\email\thirdparty\phpmailerException;

require_once __DIR__ . 'third_party/class.phpmailer.php';
//require_once __DIR__.'third_party/class.smtp.php';
//require_once __DIR__.'third_party/class.pop3.php';


class MailerThirdParty extends AbstractMailer{
  private $tpm;

  /**
   * MailerThirdParty constructor.
   */
  public function __construct() {
    $this->tpm = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
  }

  /**
   * @param Message $message
   * @return bool
   */
  public function send(Message $message) {
    $mail = $this->tpm;
    try {
      if ($message->replyTo)
        $mail->AddReplyTo($message->replyTo->email, $message->replyTo->email);
      $to = $message->recipients->getTo();
      foreach ($to as $recipient) {
        /** @var Contact $recipient */
        $mail->AddAddress($recipient->email, $recipient->name);
      }
      $mail->SetFrom($message->from->email, $message->from->name);
      $mail->Subject = $message->subject;
      if (!empty($message->altBody))
        $mail->AltBody = $message->altBody;
      $mail->MsgHTML($message->body);

      if (!empty($message->attachments)) {
        foreach ($message->attachments as $attachment) {
          /** @var Attachment $attachment */
          $mail->AddAttachment($attachment->file, $attachment->encoding, $attachment->type);      // attachment
        }
      }
      $mail->Send();
      return true;
    } catch (phpmailerException $e) {
      return false;
//      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (\Exception $e) {
      return false;
//      echo $e->getMessage(); //Boring error messages from anything else!
    }
  }

}
