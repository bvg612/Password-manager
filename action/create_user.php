<?php

namespace action;

use app\db\Record;
use app\db\User;
use app\forms\RecordForm;
use app\forms\UserForm;
use Exception;
use nsfw\auth\PasswordHash;
use nsfw\controller\AbstractAction;
use nsfw\exception\UserException;
use nsfw\validators\SimpleValidator;
use nsfw\validators\ValidatorEmail;


class create_user extends AbstractAction {
  /** @var RecordForm */
  protected $form;


  function runEnd() {
    // $tpl = $this->createCenterTemplate('create_user.html');
    $this->init();
    $this->processPost();
  }

  protected function init() {
    $ns = ns();

    if(!$ns->isAdmin) {
      $this->errorReporter->errorRedirect('/', 'You must be administrator to do that');
    }

    $this->form = $form = new UserForm();
    $tpl = $this->setCenterForm($form);

    return $this->form;
  }

  private function processPost() {
    $ns = ns();
    $db = $ns->db;
    if($_SERVER['REQUEST_METHOD'] != 'POST') {
      return;
    }


    $db->startTransaction();
    try {
      $form = $this->form;
      if(!$form->processPost()) {
        $db->rollback();

        return;
      }

      if($form->getField('id')->value > 0) {
        $user = User::createById($db, $form->getField('id')->value);
      } else {
        $user = new User();
      }
      $user->import($form->getData());

      $password2 = $form->getField('password2')->value;
      if($password2 != $user->password) {
        //ToDo: error for re-enter password
      }

      $data = $user->exportDb();
      $ph = new PasswordHash();

      $action = '';


      $email = $data['email'];
      $validator = new ValidatorEmail();
      if(!$validator->validateValue($email)) {
        throw new UserException('The email you entered is not valid.');
      }

      $row = $db->queryFirstRow('SELECT * FROM users WHERE email = ' . $db->quote($email) . ' FOR UPDATE');

      if($db->foundRows() > 0 && $row['id'] != $data['id']) {
        throw new UserException('A user with this email already exists!');
      }

      if(!empty($data['id'])) {
        // Edit user
        //$id = $data['id'];
//        if($data['password'] == '') {
//          unset($data['password']);
//        } else {
//          $data['password'] = $ph->passwordHash($data['password'], $user->id);
//        }
        if(!empty($data['password'])) {
          //ToDo: remove this exception.
          // ToDo: regenerate the private key. First try to delete the private key to see if it gets auto-generated on login
          //          $data['key'] = '';
          throw new UserException('You can\'t change your password yet ');
        }
        unset($data['password']);
        $db->simpleUpdate(User::getTable(), $data, 'id');
        $action = 'updated';

      } else {
        // Create user

        $password = $data['password'];
        $data['password'] = '';

        unset($data['id']);

        $user->id = $db->insert(User::getTable(), $data);

        $user->password = $passwordHash = $ph->passwordHash($password, $user->id);
        $db->simpleUpdate($user::getTable(), [
          'id' => $user->id,
          'password' => $user->password
        ], 'id');


        $action = 'created';
      }

      $db->commit();

      $this->errorReporter->infoRedirect('/users.html', 'User has been ' . $action . '. ');

    } catch (Exception $e) {
      $db->rollback();
      // var_dump($e);
      $this->errorReporter->handleException($e);
    }
  }
}
























