<?php

namespace action;



use nsfw\controller\AbstractAction;

class users extends AbstractAction {

  function runEnd() {
    $ns = ns(); $db = $ns->db;

    if(!$ns->isAdmin) {
      $this->errorReporter->errorRedirect('/', 'You must be administrator to go here');
    }

    $tpl = $this->createCenterTemplate('users.html');
    $users = $db->queryRows('SELECT id, name, email FROM users');
    $bUsers = $tpl->getBlock('users');
    foreach($users as $user) {
      $bUsers->appendRow([
        'userId' => $user['id'],
        'name'   => $user['name'],
        'email'   => $user['email'],
      ]);
    }
  }
}
