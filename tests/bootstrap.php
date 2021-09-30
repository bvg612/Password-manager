<?php

namespace {

  if(!defined('SIGUSR1'))
    define ('SIGUSR1', 10);

  use nsfw\auth\PasswordHash;
  use nsfw\database\Databasei;


  defined('TESTS_DIR') || define('TESTS_DIR', __DIR__);
  defined('PROJECT_DIR') || define('PROJECT_DIR', __DIR__.'/..');
  defined('TESTS_DATA_DIR') || define('TESTS_DATA_DIR', TESTS_DIR . '/data');
  require_once __DIR__.'/../config.inc.php';

  require __DIR__ . "/../nsfw/common.inc.php";
  require_once __DIR__ . '/../functions.inc.php';
  require_once __DIR__ . '/../config.inc.php';
  require_once __DIR__ . '/../Config.php';
  require_once __DIR__ . '/../classes/NS.php';


  $loader = \nsfw\Autoloader::getInstance();
  $loader->addPsr4('app', __DIR__ . '/../classes');


  $testDbCredentials = [
    'dbHost' => '10.5.0.1:3306',
    'dbName' => 'ypassman',
    'dbUser' => 'yanica',
    'dbPass' => '....',
    'socket' => '',
    'charset' => 'utf8',
  ];

  $ns = NS::getInstance();
//  $ns->db = new Databasei($testDbCredentials);
//  $ns->db2 = clone $ns->db;
//  $ns->db->connect();
//  $cache = new \nsfw\cache\MemCache('', [
//    'host' => '10.0.0.6',
//    'port' => 11211,
//  ]);
//  $ns->setCache($cache);
//  $ns->setReadonlyFields();
//  PasswordHash::init('RoZ20Ew4QXWnfhhaFLRBXmhLYx', PasswordHash::SHA512_10K);

}

namespace nsfw {

  use nsfw\template\CascadedTemplate;
  use nsfw\template\TemplateConfig;

  if(!function_exists('getOS')) {
    function getOS() {
      static $os = null;
      if(!empty($os))
        return $os;
      if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $os = 'WINDOWS';
      } else if(strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
        $os = 'LINUX';
      } else {
        $os = 'UNKNOWN';
      }

      return $os;
    }
  }

  $config = new TemplateConfig();
  $config->mainDir = TESTS_DIR.'/data/tpl/default';
  $config->subtemplateDir = TESTS_DIR.'/data/tpl/subtemplate';
  CascadedTemplate::setDefaultConfig($config);

}


namespace {
  if(\nsfw\getOS() == 'LINUX') {
    $testDbCredentials['dbHost'] = '127.0.0.1:3306';
  }
}
