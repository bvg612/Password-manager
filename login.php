<?php

use app\user\LoginSession;
use nsfw\auth\PasswordHash;
use nsfw\template\CascadedTemplate;

require_once __DIR__ . '/bootstrap.php';

$userId = getParam('userId', 0, 'PG');

$sess = new LoginSession($db);

if($sess->isLogged())
  httpRedirect('./');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if(empty($userId))
    httpRedirect('/');
  $password = getParam('password', 'PG');
  $success = $sess->login($userId, $password);
//  var_dump('login result: ', $success, $sess->isLogged());

  if($success)
    httpRedirect('/');

  return;
} else if(empty($userId)) {
  $users = $db->queryRows('SELECT id, name FROM users');
  $tpl = CascadedTemplate::createFromFile('login.html');
  foreach($users as $user) {
    $tpl->appendRow([
      'userId' => $user['id'],
      'name'   => $user['name'],
    ]);
  }
  $tpl->display();
  return;
}


$user = $db->queryFirstRow('SELECT id, name FROM users WHERE id = '.intval($userId));

?>
<div style="width: 300px;border: 1px solid #414141;padding: 10px;margin: 50px auto auto auto;">
<form action="login.php" method="post">
  <input type="hidden" name="userId" value="<?=$userId?>" />
  Name: <?=$user['name']?> (<a href="login.php">change</a>)<br />
  <br />
password: <input name="password" type="password" />
<input type="submit" value="Login">

</form>
</div>
