<?php

namespace action;

use app\openssl\OpenSslKey;
use app\user\LoginSession;
use nsfw\controller\AbstractAction;
use nsfw\database\dbException;
use nsfw\template\CascadedTemplate;


class login extends AbstractAction {
  /** @var LoginSession */
  protected $sess;

  function runEnd() {
    $ns = ns(); $db = $ns->db;


    $userId = getParam('userId', 0, 'PG');
    $sess = $this->sess = new LoginSession($db);
    $this->processPost();
    if($sess->isLogged())
      httpRedirect('/');
    if(empty($userId)) {
      $tpl = $this->createCenterTemplate('login.html');
      $users = $db->queryRows('SELECT id, name FROM users');
      $bUsers = $tpl->getBlock('users');
      foreach($users as $user) {
        $bUsers->appendRow([
          'userId' => $user['id'],
          'name'   => $user['name'],
        ]);
      }
      if(false)
        $tpl->setVar('debug', 'debug'.var_export($_COOKIE, true));
      return;
    }
    $tpl = $this->createCenterTemplate('enter-password.html');
    $user = $db->queryFirstRow('SELECT id, name FROM users WHERE id = '.intval($userId));
    $tpl->setVars([
      'userId' => $user['id'],
      'name' => $user['name'],
    ]);
  }

  private function processPost() {
    if($_SERVER['REQUEST_METHOD'] !== 'POST')
      return false;
    $sess = $this->sess;

    $userId = getParam('userId', 0, 'PG');

    if(empty($userId))
        httpRedirect('/login.html');
    $password = getParam('password', 'PG');
    try {
      $success = $this->sess->login($userId, $password);
      //  var_dump('login result: ', $success, $sess->isLogged());
      if($success) {
//        $this->createPrivateKey($userId, $password);
        httpRedirect('/');

        return true;
      }
    }catch (\Exception $e) {
      $url  = '/login.html';
      if(!empty($userId))
        $url .= '?userId='.$userId;
      $this->errorReporter->handleException($e, $url);
    }
    return false;
  }

  public function createPrivateKey(int $userId, $password) {
    $ns = ns(); $db = $ns->db;

    $user = $db->queryFirstRow('SELECT * FROM users WHERE id = '.intval($userId));
    if(!empty($user['key'])) {
      return;
    }
    $key = new OpenSslKey();
    $key->generate();
    $key->setPassword($password);
    $db->simpleUpdate('users', [
      'id' =>$userId,
      'key' => $key->exportPrivate($password, $password),
    ]);
  }


}
