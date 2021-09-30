<?php

namespace action;

use app\db\EncryptedRecord;
use app\db\Record;
use app\forms\RecordForm;
use app\openssl\OpenSslKey;
use app\user\LoginSession;
use Exception;
use nsfw\auth\PasswordHash;
use nsfw\controller\AbstractAction;
use nsfw\exception\UserException;


class newrecord extends AbstractAction {
  /** @var RecordForm */
  protected $form;
  /** @var OpenSslKey */
  protected $key;

  function runEnd() {
//    $tpl = $this->createCenterTemplate('newrecord.html');
    $this->init();
    $this->processPost();
  }

  protected function init() {
//    $mainTpl = $this->pageController->getTemplate();
    $this->form = $form = new RecordForm();
    $tpl = $this->setCenterForm($form);
    return $this->form;
  }

  private function processPost() {
    $ns = ns(); $db = $ns->db;
    if($_SERVER['REQUEST_METHOD'] != 'POST')
      return;

    $db->startTransaction();
    try {
      $form = $this->form;
      if(!$form->processPost()) {
        $db->rollback();
        return;
      }


      $er = new EncryptedRecord();
      if($form->getField('id')->value >0) {
        $record = Record::createById($db, $form->getField('id')->value);
        $er->importEncryptedRecord($record);
        if(!$er->verifyHash())
          throw new UserException('This record has been modified outside this application - editing disabled!');
      } else {
        $record = new Record();
        $record->userId = $ns->userId;
      }
      $record->import($form->getData());
      $er->importRecord($record);

      if($record->userId != $ns->userId)
        throw new UserException('This record does not belong to you');

      $data = $er->getEncryptedData();

      $action = 'created';
      if(!empty($data['id'])) {
        $id = $data['id'];
        $db->simpleUpdate(Record::getTable(), $data, 'id');
        $action = 'updated';
      } else {
        unset($data['id']);
        $data['user_id'] = $ns->userId;
        $id = $record->id = $db->insert(Record::getTable(), $data);
      }
      $db->commit();
      $this->errorReporter->infoRedirect('/', 'Record has been '.$action);
    } catch (Exception $e) {
      $db->rollback();
//      $ns->errorReporter->setDebug(true);
        $ns->errorReporter->handleException($e);
    }
  }

  public function verifyHash($data) {
    $hash = $data['hash'];
    static $hashFields = [
      'title',
      'login',
      'password',
      'url',
      'description',
      'secure_note',
    ];
    $hashData = 'secure_check:';
    foreach($hashFields as $hashField) {
      $hashData .= $data[$hashField];
    }
    return strtoupper($hash) == strtoupper(PasswordHash::sha512($hashData));
  }

  private function calcHash(array $data) {
    static $hashFields = [
      'title',
      'login',
      'password',
      'url',
      'description',
      'secure_note',
    ];
    $hashData = 'secure_check:';
    foreach($hashFields as $hashField) {
      $hashData .= $data[$hashField];
    }
    return PasswordHash::sha512($hashData);
  }

  /**
   * @return OpenSslKey
   */
  private function getKey() {
    if(empty($this->key)) {
      $key = new OpenSslKey();
      $sess = new LoginSession(ns()->db);
      $key->importPrivate(getParam('key', '', 'S'), $sess->rot13(getParam('kp', '', 'C')));
      $this->key = $key;
    }
    return $this->key;
  }

}
