<?php

use nsfw\auth\PasswordHash;
use nsfw\ControlledShutdown;
use nsfw\database\Databasei;
use nsfw\database\SqliteDatabase;
use nsfw\errors\ErrorReporter;
use nsfw\session\DbSession;
use nsfw\template\CascadedTemplate;


require __DIR__ . "/nsfw/common.inc.php";
require_once __DIR__ . '/functions.inc.php';
require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/Config.php';

$loader = \nsfw\Autoloader::getInstance();
$loader->addPsr4('app', __DIR__ . '/classes');

require_once __DIR__ . '/classes/NS.php';
$ns = NS::getInstance();

$projectDir = dirname(dirname(getenv('OPENSSL_CONF')));
if(empty($projectDir)) {
  $projectDir = dirname(__DIR__);
}

$c = getConfig();
$c->loadFromPhp(__DIR__ . '/config.inc.php');
$tplConfig = CascadedTemplate::getDefaultConfig();
$tplConfig->tplRootDir = __DIR__. '/tpl';
$tplConfig->mainDir = $tplConfig->tplRootDir;
CascadedTemplate::initDefaultHtmlProcessors(false);

//$tplConfig->setLoaderScript('rl.php');

//$db = new SqliteDatabase($projectDir . '/db.sqlite');
//$db->connect();
//$ns->db = $db;
//$db->query('PRAGMA foreign_keys = ON');

$db = new Databasei($dbCredentials);
$db->connect();
$ns->db = $db;

//var_dump($db->isConnected());



$session = new DbSession();
$session->startDb($db);
$ns->session = $session;

$ns->errorReporter = $er = new ErrorReporter(true);
$ns->jsFiles = [
  'js/common.js' => [
    'js/ns.js',
    'js/functions.js',
    'js/ajax.js',
    'js/jscolor.js',
    'js/ColorEditor.js',
    'js/CharCounter.js',
    'js/QuickFill.js',
    'js/ButtonPreview.js',
    'js/ActionForm.js',
    'js/CheckboxSelector.js',
  ]
];

$cs = ControlledShutdown::getInstance();
$cs->registerCallback(function () use ($session, $db) {
  $session->close();
  $db->close();
});
$loginSession = new \app\user\LoginSession($db);
$loginSession->loadSession();
$ns->loginSession = $loginSession;


PasswordHash::init('RoZ20Ew4QXWnfhhaFLRBXmhLYx', PasswordHash::SHA512_10K);

if(preg_match('@^/login\\.html@',$_SERVER['REQUEST_URI'])) {
//  echo 'not logged in '; var_dump($_SESSION);exit;
//    if($loginSession->isLogged())
//      httpRedirect('/');
} else if(preg_match('@^/create_user\\.html@',$_SERVER['REQUEST_URI'])) {

}else {
  if(!$loginSession->isLogged()) {
//    echo 'not logged in '; var_dump($_SESSION);exit;
    $er->errorRedirect('/login.html', 'Please login first');
//  httpRedirect('/login.html');
  }
}

$ns->setReadonlyFields();

//$timeSinceLastVacuum = $db->queryFirstField('SELECT strftime("%s","now") - strftime("%s", value) FROM vars WHERE name = "last_vacuum"');
//if($timeSinceLastVacuum > 3600*24*7) {
//  $db->query('VACUUM');
//  $db->query('update vars set value = datetime("now") WHERE name="last_vacuum"');
//}
