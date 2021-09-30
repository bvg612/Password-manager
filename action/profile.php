<?php

namespace action;

use app\openssl\OpenSslKey;
use nsfw\auth\PasswordHash;
use nsfw\controller\AbstractAction;
use nsfw\exception\UserException;


class profile extends AbstractAction {

  function runEnd() {
    $ns = ns(); $db = $ns->db;

    $tpl = $this->createCenterTemplate('profile.html');
    $name = $db->queryFirstField('SELECT name FROM users WHERE id = '.$ns->userId);
    $name = getParam('name', $name, 'P');
    $tpl->setVar('name', $name);
    $action = getParam('action', 'P');
    switch($action) {
      case 'set-password':
        $this->setPassword();
        break;
      case 'set-name':
        $this->setName();
        break;
    }
  }

  private function setPassword() {
    $ns = ns(); $db = $ns->db;

    $currentPassword = getParam('cpassword','', 'P');
    $newPassword = getParam('newpassword','', 'P');
    $newPassword2 = getParam('newpassword2','', 'P');
    if(empty($currentPassword))
      throw new UserException('Please enter the current password');
    $ph = new PasswordHash();
    $user = $ns->loginSession->getUser();
    $userId = $user['id'];
    if(empty($user)) {
      throw new UserException('Cannot fetch user information. Please login again');
    }
    if(!$ph->checkPassword($currentPassword, $user['password'], $userId)) {
      throw new UserException('Current password is wring');
    }

    if(empty($newPassword))
      throw new UserException('Please enter the new password');

    if($newPassword !== $newPassword2)
      throw new UserException('New passwords do not match');

    $key = new OpenSslKey();
    $key->importPrivate($user['key'], $currentPassword);
    $newKey = $key->exportPrivate($currentPassword, $newPassword);
    $db->startTransaction();
    try {
      $db->simpleUpdate('users', [
        'id' => $userId,
        'key' => $newKey,
        'password' => $ph->passwordHash($newPassword, $userId),
      ]);
      $db->commit();
      $ns->errorReporter->infoRedirect('/profile.html', 'Password has been changed!');
    }catch (\Exception $e) {
      $db->rollback();
      throw $e;
    }
  }

  private function setName() {
    $ns = ns(); $db = $ns->db;

    $name = getParam('name', '', 'P');
    $name = trim($name);
    if(empty($name))
      throw new UserException('Name cannot be empty');

    if(strlen($name) < 3)
      throw new UserException('Name must contain at least 3 letters');

    $db->simpleUpdate('users', [
      'id'=>$ns->userId,
      'name'=>$name,
    ]);
    $ns->errorReporter->infoRedirect('/profile.html', 'Name has been changed!');
  }

}
