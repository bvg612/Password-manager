<?php

namespace nsfw\i18;


use Exception;
use nsfw\cache\Cache;
use nsfw\cache\NullCache;

abstract class AbstractLanguage implements Language{
  public static $cacheTtl = 86400; // 24h
  protected static $languages = ['EN'];
  /** @var Language */
  protected static $instance;

  /** @var string */
  protected $lang = 'EN';

  protected $langStr = [];

  /** @var string */
  protected $encoding = 'utf8';

  /** @var Cache */
  protected $cache;

  /** @var bool */
  protected $autoAddVars = true;

  /**
   * DbLanguage constructor.
   */
  public function __construct() {
    $this->cache = NullCache::createInstance();
    if(empty(static::$languages))
      throw new Exception('Set languages before you create an instance');
    $this->lang = static::$languages[0];
  }

  public function __destruct() {
    $this->cache->saveCache();
  }

  /**
   * @return mixed
   */
  public function getAutoAddVars() {
    return $this->autoAddVars;
  }

  /**
   * @param mixed $autoAddVars
   */
  public function setAutoAddVars($autoAddVars) {
    $this->autoAddVars = $autoAddVars;
  }

  /**
   * @param string $lang Language to load
   * @return array|false Must return language in form of [ <langVar> => <translation> ] or false on failure
   */
  abstract protected function _loadLanguage($lang);

  public function langLoaded($lang) {
    if(!array_key_exists($lang, $this->langStr))
      return false;
    if($this->langStr[$lang] === false || $this->langStr[$lang] === null)
      return false;
    return true;
  }

  public function purgeAllLangCache() {
    foreach(self::$languages as $lang) {
      $this->purgeCache($lang);
    }
  }
  public function purgeCache($lang) {
    $this->cache->setExpired($this->getCacheLangName($lang));
  }

  public function storeAllLangsToCache() {
    foreach(self::$languages as $lang) {
      $this->storeLangToCache($lang);
    }
  }

  public function storeLangToCache($lang = null) {
    if(empty($lang))
      $lang = $this->lang;

    if(strtolower($lang) == 'all') {
      $this->storeAllLangsToCache();
      return;
    }

    if($this->langLoaded($lang))
      $this->cache->put($this->getCacheLangName($lang), $this->langStr[$lang], self::$cacheTtl);
  }

  public function getLanguageFromCache($lang) {
    return $this->cache->get($this->getCacheLangName($lang), false);
  }

  public function loadLanguage($lang = null) {
    if(empty($lang))
      $lang = $this->lang;

    $langStr = $this->getLanguageFromCache($lang);
    if($langStr === false) {
      $langStr = $this->_loadLanguage($lang);
    }
    if($langStr === false)
      throw new Exception('Cannot load language "'.$lang.'" using '.get_class($this));

    $this->langStr[$lang] = $langStr;
    return true;

  }

  /**
   * @param string $lang
   * @return string
   * @internal param string $varName
   */
  protected function getCacheLangName($lang) {
    return $lang;
  }

  /**
   * @param array|string $langs
   * @throws Exception
   */
  public static function addLanguages($langs){
    if(is_string($langs))
      $langs = [$langs];

    if(!is_array($langs))
      throw new Exception('$langs must be array or string');

    self::$languages = $langs + self::$languages;
  }

  /**
   * @return array
   */
  public static function getLanguages(){
    return self::$languages;
  }

  /**
   * @param array|string $langs
   * @throws Exception
   */
  public static function removeLanguages($langs) {
    if(is_string($langs))
      $langs = [$langs];

    if(!is_array($langs))
      throw new Exception('$langs must be array or string');

    self::$languages = array_diff(self::$languages, $langs);
  }

  /**
   * @return Cache
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * @param Cache $cache
   */
  public function setCache($cache) {
    $this->cache = $cache->getInstance('dblang');
  }

  /**
   * @return string Returns the current language
   */
  public function getLang() {
    return $this->lang;
  }

  public function setLang($lang) {
    $this->lang = $lang;
  }

  public function getEncoding() {
    return $this->encoding;
  }

  public function setEncoding($encoding) {
    $this->encoding = $encoding;
  }

  /**
   * Updates single language variable internally. Does not change storage or cache
   *
   * @param string$lang
   * @param string $varName
   * @param string $value
   * @return bool
   */
  protected function updateInternalVar($lang, $varName, $value) {
    if(!$this->langLoaded($lang))
      $this->loadLanguage($lang);

    if(!array_key_exists($lang, $this->langStr))
      return false;

    $this->langStr[$lang][$varName] = $value;

    return true;
  }

  /**
   * @param string $langVar
   * @param null $lang
   * @return bool
   */
  public function langVarExists($langVar, $lang = null) {
    if(empty($lang))
      $lang = $this->lang;
    return array_key_exists($langVar, $this->langStr[$lang]);
  }

  /**
   * @param string $langVar
   * @param null $lang
   * @return bool
   */
  public function isLangVarSet($langVar, $lang = null) {
    if(empty($lang))
      $lang = $this->lang;
    if(!array_key_exists($langVar, $this->langStr[$lang]))
      return false;
    return isset($this->langStr[$lang][$langVar]);
  }

  /**
   * @return string
   */
  protected function getTraceForAddVar() {
    $trace = '';
    $bt = debug_backtrace();
    $usageIndex = 0;
    foreach($bt as $index => $btx) {
      if(!empty($btx['object'])) {
        $obj = $btx['object'];
        if($obj instanceof Language) {
          continue;
        }
      }
      $usageIndex = $index;
      break;
    }

    $bti = $bt[$usageIndex];
    $bti['object'] = null;
    $file = '/unknown/';
    $line = 0;

    if(!empty($bti['line']))
      $line = $bti['line'];
    if(isset($bti['file']))
      $file = $bti['file'];

    $trace = $file.':'.$line;
    if(!empty($bt[$usageIndex+1])) {
      $btNext = $bt[$usageIndex+1];
      $trace .= ', ';
      if(!empty($btNext['object']))
        $trace .= get_class($btNext['object']) . $btNext['type'];

      if(!empty($btNext['function']))
        $trace .= $btNext['function'] . '()';

    }
    return $trace;
  }

  public function translate($langVar, $lang = null) {
    if(empty($lang))
      $lang = $this->lang;

    if(!$this->langLoaded($lang))
      $this->loadLanguage($lang);

    if(!$this->langVarExists($langVar)) {
      $this->addLangVar($langVar, [], 'Added by PHP at '. $this->getTraceForAddVar());
    }

    if(!$this->isLangVarSet($langVar))
      return '';

    return $this->langStr[$lang][$langVar];
  }

  public function translateUFirst($langVar, $lang = null) {
    $str = $this->translate($langVar, $lang);
    $firstChar = mb_substr($str, 0, 1, $this->encoding);
    $firstChar = mb_strToUpper($firstChar, $this->encoding);
    return $firstChar.mb_substr($str, 1, 100000, $this->encoding);
  }

  public function translateUWords($langVar, $lang = null) {
    $str = $this->translate($langVar, $lang);
    return mb_convert_case($str, MB_CASE_TITLE, $this->encoding);
  }

  public function translateUpper($langVar, $lang = null) {
    return mb_strtoupper($this->translate($langVar, $lang), $this->encoding);
  }

  public function translateLower($langVar, $lang = null) {
    return mb_strtolower($this->translate($langVar, $lang), $this->encoding);
  }



}
