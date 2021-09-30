<?php

namespace action;

use nsfw\controller\AbstractAction;


class del_user extends AbstractAction {


  function runEnd() {
    $ns = ns(); $db = $ns->db; $er = $this->errorReporter;

    //$tpl = $this->createCenterTemplate('del_user.html');
    if(!$ns->isAdmin) {
      $this->errorReporter->errorRedirect('/', 'You must be administrator to do that');
    }

    $id = (int)getParam('id', 0, 'P');
    if(empty($id))
      $this->errorReporter->errorRedirect('/', 'User does not exist');
    try {
      $db->query('DELETE FROM users WHERE id = ' . $id);

      $er->infoRedirect('/users.html', 'User was deleted');
    }catch (\Exception $e) {
      $er->handleException($e, '/');
    }
  }

}
