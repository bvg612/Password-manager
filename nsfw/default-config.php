<?php

use nsfw\i18\NullLanguage;

$projectRoot = dirname(__DIR__);
$config = [
  'classPath' => $projectRoot,
  'projectRoot' => $projectRoot,
  'webroot' => $projectRoot.'/www',
  'webPath' => '/',
  'webHost' => 'localhost',
  'translator' => new NullLanguage(),
];

require $config['classPath'].'/nsfw/forms/form_fields.inc.php';
