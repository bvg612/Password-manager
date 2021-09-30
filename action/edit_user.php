<?php

namespace action;

use app\db\User;
use nsfw\forms\AbstractFormField;

require_once __DIR__.'/create_user.php';

class edit_user extends create_user {

  protected function init() {
    $ns = ns(); $db = $ns->db;
    $form =  parent::init();
    $userId = getParam('id', 0, 'PG');

    $tpl = $form->getTemplate();
    $tpl->setVar('passwordHelp', '<br />If you leave the password field blank it won\'t change');

    if(empty($userId)) {
      $ns->errorReporter->errorRedirect('/', 'User ' . $userId . ' does not exist');
    }

    $user = User::createById($db, $userId);

    $data = $user->exportDb();
    $data['password'] = '';
    $form->setData($data);
    return $form;
  }

}
