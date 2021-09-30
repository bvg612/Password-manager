<?php

namespace action\test;

use action\import;
use app\db\Record;
use app\db\User;
use nsfw\controller\AbstractAction;
use nsfw\exception\UserException;
use nsfw\forms\PasswordField;
use nsfw\forms\TemplateForm;


class mytest extends AbstractAction {


  function runEnd() {
    $ns = ns(); $db = $ns->db;

    $db = ns()->db;


//    var_dump($db->queryAssocSimple('SELECT * FROM users  LIMIT 5'));
//    var_dump(User::createById($db, '33'));


    $form = new TemplateForm();
    $form->addNewField('username', 'text');
    $form->addNewField('pass', 'password');

    $ff = new PasswordField('pass');
    $form->addField($ff);


    $tpl = $this->createCenterTemplate('mytest.html');
    $tpl->setVar('varName', 'Variable');

    $result = htmlspecialchars('<a href="#">this is not escaped</a>');
    $this->errorReporter->addErrors('this is the first error');
    try {

      $bRow = $tpl->getBlock('row');
//    $bRow = $tpl->row;

      $bRow->appendRow(['title' => $result]);

      throw new UserException('this is an error');

      $row2 = $bRow->appendRow(['title' => 'row2']);
      $bRow->appendRow(['title' => 'row3']);

      $row2->setVar('title', 'row2 changed');
      //$row2->title = 'by property';
    }catch (\Exception $e) {
      $this->errorReporter->handleException($e);
    }
  }

}
