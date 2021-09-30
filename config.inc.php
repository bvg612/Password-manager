<?php
$projectRoot = __DIR__;
$projectDir = dirname(dirname(getenv('OPENSSL_CONF')));

if(!defined('PROJECT_DIR')) {
  define('PROJECT_DIR', $projectRoot);
}
$config = [
  'webHost' => 'nsfw3.linux.loc',
  'projectRoot' => $projectRoot,
  'classPath' => $projectRoot.'/nsfw',
  'webPath' => '/',
  'webroot' => __DIR__,
  'iniFile' => __DIR__.'/fe/settings.ini',
];


$dbCredentials = array(
  'dbHost' => '127.0.0.1',
  'dbName' => 'passman',
  'dbUser' => 'root',
  'dbPass' => '653533',
);
