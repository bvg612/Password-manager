<?php

namespace action;

use app\user\LoginSession;
use nsfw\controller\AbstractAction;


class logout extends AbstractAction {


  function runEnd() {
    $ns = ns(); $db = $ns->db;

//    $tpl = $this->createCenterTemplate('logout.html');
    $sess = new LoginSession($db);
    $sess->logout();
    httpRedirect('/');
  }

}
