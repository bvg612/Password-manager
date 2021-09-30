<?php

use nsfw\template\CascadedTemplate;

require_once __DIR__ . '/bootstrap.php';

$tpl = CascadedTemplate::createFromFile('create-user.html');

//foreach($users as $user) {
//  $tpl->appendRow([
//    'userId' => $user['id'],
//    'name'   => $user['name'],
//  ]);
//}
$tpl->display();
