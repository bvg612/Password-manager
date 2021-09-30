<?php


namespace app\db;


use app\openssl\OpenSsl;
use app\openssl\OpenSslKey;
use app\user\LoginSession;
use nsfw\auth\PasswordHash;

class EncryptedRecord {
  /** @var Record */
  private $record;
  /** @var OpenSsl */
  private $ssl;
  /** @var OpenSslKey */
  private $key;
  /** @var string */
  private $keyPassword;
  private $hashCorrect = false;

  /**
   * EncryptedRecord constructor.
   *
   * @param Record|null $record
   */
  public function __construct() {
    $ssl = $this->ssl = new OpenSsl();
    $key = new OpenSslKey();
    $this->record = new Record();
    $sess = new LoginSession(ns()->db);
    $this->keyPassword = $keyPass = $sess->rot13(getParam('kp', '', 'C'));

    $key->importPrivate(getParam('key', '', 'S'), $keyPass);
    $this->key = $key;
  }

  public function importRecord(Record $record) {
    $this->record->import($record->export());
  }

  public function importEncryptedRecord(Record $record) {
    $ssl = $this->ssl;
    $key = $this->key;
    $data = $record->export();
    $data['password'] = $ssl->privateDecryptBase64($data['password'], $key, $this->keyPassword);
    $data['secureNote'] = $ssl->privateDecryptBase64($data['secureNote'], $key, $this->keyPassword);
    $this->record->import($data);
    $this->hashCorrect = $this->isHashCorrect($data);
  }

  public function getData() {
    return $this->record->export();
  }

  public function getEncryptedData($recalcHash = true) {
    $data = $this->record->exportDb();
    if(empty($data['id'])) {
      $data['id'] = null;
    }
    $ssl = $this->ssl;
    $key = $this->key;
    if($recalcHash) {
      $data['hash'] = $this->calcHash($data, true);
    }
    $data['password'] = $ssl->publicEncryptBase64($data['password'], $key->exportPublic());
    $data['secure_note'] = $ssl->publicEncryptBase64($data['secure_note'], $key->exportPublic());
    return $data;
  }

  public function verifyHash() {
    return $this->isHashCorrect($this->record->export());
  }

  /**
   * @param array $data
   * @param bool $isDb Use database field names
   *
   * @return bool|string
   */
  public function calcHash(array $data, $isDb = false) {
    static $hashFields = [
      'title',
      'login',
      'password',
      'url',
      'description',
      'secureNote',
    ];

    $hashFields[5] = $isDb?'secure_note':'secureNote';

    $hashData = 'secure_check:';
    foreach($hashFields as $hashField) {
      $hashData .= $data[$hashField];
    }

    return PasswordHash::sha512($hashData);
  }

  private function isHashCorrect($data) {
//    if(strtoupper($data['hash']) != strtoupper($this->calcHash($data))) {
//      var_dump(strtoupper($data['hash']), strtoupper($this->calcHash($data)));
//    }
    return strtoupper($data['hash']) == strtoupper($this->calcHash($data));
  }

}
