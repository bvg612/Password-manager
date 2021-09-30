<?php


use nsfw\cache\Cache;
use nsfw\controller\PageControllerUrl;
use nsfw\database\Databasei;
use nsfw\database\SqliteDatabase;
use nsfw\errors\ErrorReporter;
use nsfw\i18\Language;
use nsfw\nsObject;
use nsfw\session\DbSession;


/**
 * Class NS
 * @package res
 * @property int|null $accountId
 * @property int|null $userId
 * @property Databasei $db
 * @property Databasei $sdb
 * @property DbSession $session
 * @property bool $isLogged
 * @property bool $isAdmin
 * @property ErrorReporter $errorReporter
 * @property \app\user\LoginSession $loginSession
 * @property Config $config
 * @property Language $language
 * @property array jsFiles
 */
class NS extends nsObject {

  private static $instance;
  /** @var Databasei */
  protected $db;

  /** @var Databasei */
  protected $_sdb;

  /**
   * NS constructor.
   */
  private function __construct() {
    $this->allowAddNewFields = true;
    $this->config = Config::getInstance();
    $this->readonly['sdb'] = true;
    $this->readonly['commonJs'] = true;
  }

  public static function getInstance() {
    if(empty(self::$instance))
      self::$instance = new static();

    return self::$instance;
  }

  public function config($confVar) {
    return $this->config->getVar($confVar);
  }

  public function setReadonlyFields($moreReadonlyFields = []) {
    $this->readonly = array_merge($this->readonly, [
      'loginSession' => true,
      'db'           => true,
      'session'      => true,
    ]);
    if(!empty($moreReadonlyFields))
      $this->readonly = array_merge($this->readonly, $moreReadonlyFields);
  }

  public function includeJsFiles(PageControllerUrl $pc, $commonJs, array $list) {
    $useMin = true;
    $useCommon = true;
    if(ns()->config->jsDevelop) {
      $useCommon = false;
      $useMin = false;
    }
    if($useCommon) {
      if($useMin) {

      }
      $pc->addJsFile($commonJs);

      return;
    }

    foreach($list[$commonJs] as $jsFile) {
      $pc->addJsFile('/' . $jsFile);
    }
  }


  public function getUserId() {
    $userId = getParam('user_id', 0, 'S');
    if(empty($userId))
      return 0;
    return intval($userId);

//    $ls = $this->loginSession;
//    if($ls->isLogged())
//      return intval($ls->user->id);
//
//    return 0;
  }

  /**
   * @return \db\User
   */
  public function getUser() {
    $ls = $this->loginSession;
    if($ls->isLogged())
      return $ls->user;

    return null;
  }

  public function getIsLogged() {
    return $this->loginSession->isLogged();
  }

  public function getIsAdmin() {
    return $this->loginSession->isAdmin();
  }

  public function setCache(Cache $cache) {
    $this->fields['cache'] = $cache;
  }

  /**
   * @param $namespace
   *
   * @return Cache
   */
  public function getCache($namespace = false) {
    if(empty($namespace))
      return $this->fields['cache'];
    /** @var Cache $cache */
    $cache = $this->fields['cache'];

    return $cache->getInstance($namespace);
  }

  public function getLastMenuPage() {
    $ls = $this->loginSession;
    $default = '/d/menu/';
    if(!empty($ls->place)) {
      $placeId = $ls->placeId;
      $placeTitle = $ls->placeTitle;
      $default = '/d/menu/' . $placeId . '-' . $placeTitle . '/0-top.html';
    }
    $url = getParam('lastMenuPage', $default, 'S');

    return $url;
  }

  public function getLastProductPage() {
    $default = $this->getLastMenuPage();
    $url = getParam('lastProductPage', $default, 'S');

    return $url;

  }

  public function getSdb() {

    if(empty($this->_sdb)) {
      $this->_sdb = new Databasei($GLOBALS['sphinxCred']);
      $this->_sdb->connect();
    }

    return $this->_sdb;
  }

  /**
   * @param string $name
   * @param bool $lock
   *
   * @return string|mixed
   * @throws \nsfw\database\dbException
   */
  public function getVar($name, $lock = false) {
    return \db\Vars::getInstance()->get($name, $lock);
  }

  public function setVar($name, $value) {
    \db\Vars::getInstance()->set($name, $value);
  }

  public function getVars() {
    return \db\Vars::getInstance();

  }
}

/**
 * @param string|null $var
 *
 * @return NS|mixed
 */
function ns($var = null) {
  $ns = NS::getInstance();
  if(!empty($var))
    return $ns->__get($var);

  return $ns;
}
