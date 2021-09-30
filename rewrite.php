<?php


use app\db\Vars;

require_once __DIR__ . "/bootstrap.php";

require __DIR__ . '/create_tables.php';
//$db->insert('vars', ['name'=>'test','value'=>'test1']);

try {

  $controller = new \nsfw\controller\PageControllerUrl(__DIR__.'/action');
  $controller->setErrorReporter(ns()->errorReporter);

  $content = $controller->runActions();
  echo $content;
}catch (\nsfw\database\dbException $e) {

  var_dump($e->getQuery());
  echo '<table>';
  echo $e->xdebug_message;
  echo '</table>';
}

//$vars = new Vars($db);
//$loginSession = new \app\user\LoginSession($db);
//var_dump($loginSession->isLogged());
//$loggedUser = getParam('user_id', 0, 'S');
//if(empty($loggedUser)) {
//  httpRedirect('/login.php');
//}
//
//require __DIR__ . '/encrypt.php';
//

