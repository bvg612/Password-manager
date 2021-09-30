<?php

namespace action;

use app\openssl\OpenSslKey;
use app\SearchHelper;
use app\user\LoginSession;
use nsfw\controller\AbstractAction;
use nsfw\database\display\ListTable;
use nsfw\template\Pager;


class index extends AbstractAction {

  const PER_PAGE = 10;

  function runEnd() {
    $ns = ns();
    $db = $ns->db;

    $tpl = $this->createCenterTemplate('home.html');
    $sess = new LoginSession($db);
    $key = new OpenSslKey();
    $passRot = getParam('kp', '', 'C');
    $sessionKeyPass = $sess->rot13($passRot);
//    $sessionKeyPass = $passRot;
//    $key->setPassword();

    $searchQuery = getParam('sq', '', 'G');
    $pager = new Pager('/');
    $pager->perPage = self::PER_PAGE;
    $from = (int) getParam('from', 0, 'G');
    $tpl->setVars([
      'sQuery' => $searchQuery,
      'pageNumbers' => $pager,
      'from' => $from
    ]);

    if(false) {
      $tpl->setVar('debug', var_export([
//      'logged in'=> $sess->isLogged(),
//      'importResult'=> $key->importPrivate(getParam('key', '','S'), $sessionKeyPass),
//      'key'=>getParam('key', '', 'S'),
//      'opcache'=> opcache_get_status(false),
//      'sess'=>$_SESSION,
      ], true));
    }

    $userId = getParam('user_id', 0, 'S');
    $helper = new SearchHelper();



    $rows = $db->queryRows($sql = '
        SELECT SQL_CALC_FOUND_ROWS * FROM records
        WHERE
            ' . $helper->generateSearchFilter($searchQuery) . '
          AND user_id = ' . intval($userId) . '
          LIMIT ' . intval($pager->offset) . ', ' . self::PER_PAGE . '
          ');

//    var_dump($sql);exit();


    //$rows = $db->queryRows('SELECT * FROM records WHERE user_id = ' . getParam('user_id', 0, 'S'));

    $pager->total = $db->foundRows();
    $bRecords = $tpl->getBlock('records');
    if(empty($rows)) {
//      $bRecords->hide();
      $tpl->setVar('block:records', 'No records yet!');

      return;
    }
//    $lt = new ListTable($bRecords->getBlock('row'), $rows);
//    $lt->apply();

    $bRow = $bRecords->getBlock('row');
    foreach($rows as $row) {
      $row['description'] = nl2br(htmlspecialchars($row['description']), true);
      $bRow->appendRow($row);
    }
  }

}
