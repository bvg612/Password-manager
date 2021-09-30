<?php

namespace action;

use app\db\Record;
use app\openssl\OpenSsl;
use app\openssl\OpenSslKey;
use app\user\LoginSession;
use nsfw\auth\PasswordHash;
use nsfw\controller\AbstractAction;
use nsfw\database\display\ListTable;


class showrec extends AbstractAction {


  function runEnd() {
    $ns = ns(); $db = $ns->db; $er = $ns->errorReporter;

    $tpl = $this->createCenterTemplate('showrec.html');
    $id = getParam('id', 0, 'PG');
    if(empty($id))
      httpRedirect('/');
    $record = Record::createById($db, $id);
    if(empty($record))
      $er->errorRedirect('/', 'Record '.$id.' does not exist');
    $tpl->setVar('id', $id);
    $data = $record->export();
    $ssl = new OpenSsl();
    $key = new OpenSslKey();
    $sess = new LoginSession(ns()->db);
    $keyPass = $sess->rot13(getParam('kp', '', 'C'));
    $key->importPrivate(getParam('key', '', 'S'), $keyPass);
    $data['password'] = $ssl->privateDecryptBase64($data['password'], $key, $keyPass);
    $data['secureNote'] = $ssl->privateDecryptBase64($data['secureNote'], $key, $keyPass);
    $verify = $this->verifyHash($data);
    if($verify) {
      $title = 'Shows that record has not being modified by thir party.';
      $check['Intrusion<br />Detection'] = '<img src="/img/secure-green-24.png" alt="'.$title.'" title="'.$title.'" /> Secure - this record can be trusted ';
    } else {
      $title = 'Shows that record has being modified by thir party and can NOT be trusted.';
      $check['Intrusion<br />Detection'] = '<img src="/img/insecure-red-24.png" alt="'.$title.'" title="'.$title.'"  /> '.
        '<span style="color:#ad0000;font-weight: bold">Record has been modified without permission or corrupted and can NOT be trusted</span>';
    }
    $data['password'] = '<input type="text" value="'.htmlspecialchars($data['password']).'" id="recordPassword" '.
      'readonly="readonly" class="bfField password MaskedPassword" style="width:400px;height:24px"/>';
    array_splice_assoc( $data, 1, 0, $check);
    unset($data['catId']);
    unset($data['hash']);
    $listData = [];
    foreach($data as $field=>$value) {
      $rowData =[
        'field' => $field,
        'value' => $value,
        'valueClass' => '',
        ];
      if($field == 'secureNote' || $field == 'description')
        $rowData['valueClass'] = 'fixed-width';
      $listData[] = $rowData;
    }
    $lt = new ListTable($tpl->getBlock('row'), $listData, [
      'title'=>'Title',
      ]);
    $lt->addFilter('field', function ($value, $row){
      static $titles = [
        'id' => 'ID',
        'url' => 'URL',
      ];
      if(array_key_exists($value, $titles))
        return $titles[$value];
      return ucfirst($value);
    });
    $lt->addFilter('value', function ($value, $row){
      static $skipFields = ['password','Intrusion<br />Detection'];
      $fieldName = $row['field'];
      if(in_array($fieldName, $skipFields))
        return $value;
      if($fieldName == 'url') {
        return '<a href="'.htmlspecialchars($value).'" target="_blank" rel="nofollow">'.htmlspecialchars($value).'</a>';
      }

      return nl2br(htmlspecialchars($value), true);
    });

    $lt->apply();

  }

  public function verifyHash($data) {
    $hash = $data['hash'];
    static $hashFields = [
      'title',
      'login',
      'password',
      'url',
      'description',
      'secureNote',
    ];
    $hashData = 'secure_check:';
    foreach($hashFields as $hashField) {
      $hashData .= $data[$hashField];
    }
    return strtoupper($hash) == strtoupper(PasswordHash::sha512($hashData));
  }
}
