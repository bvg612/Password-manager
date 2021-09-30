<?php

namespace nsfw\i18;


use Exception;
use nsfw\cache\Cache;
use nsfw\database\Database;
use nsfw\database\IdGen;

/**
 * Class CustomizableDbLanguage
 * @package nsfw\language
 *
 * @method static CustomizableDbLanguage getInstance(Database $db = null);
 * @method static CustomizableDbLanguage newInstance(Database $db = null);
 */
class CustomizableDbLanguage extends SingeltonLanguage {
  public static $cacheTtl = 86400; // 24h
  /** @var array */
  protected $langStr = [];
  /** @var Database */
  protected $db;
  /** @var int */
  protected $accountId;

  /**
   * DbLanguage constructor.
   *
   * @param int $accountId
   * @param Database $db
   * @param Cache|null $cache
   */
  public function __construct($accountId, Database $db = null, Cache $cache = null) {
    $this->accountId = $accountId;
    $this->db = $db;
    parent::__construct();
    if(!empty($cache))
      $this->cache = $cache;
  }

  /**
   * factory method used by Singelton class
   * @param int $accountId
   * @param Database $db
   * @param Cache|null $cache
   * @return static
   */
  public static function factory($accountId, Database $db, Cache $cache = null) {
    return new static($accountId, $db, $cache);
  }

  protected function translateImplementation($langVar, $lang) {
    // if it exists - return it asap
    if(!empty($this->langStr[$lang][$langVar]))
      return $this->langStr[$lang][$langVar];

    if(!array_key_exists($lang, $this->langStr)) {
      $this->langStr[$lang] = [];
    }

    // the var doesn't exist - load it
    return $this->loadVar($langVar, $lang);
  }

  protected function loadVar($langVar, $lang) {
    $db = $this->db;
    $translation = $db->queryFirstField('
      SELECT
          ifnull(i18_custom.translation, i18.translation) AS translation
        FROM i18
          LEFT JOIN i18_custom
            ON i18_custom.account_id = '.intval($this->accountId).'
              AND i18.lang = i18_custom.lang
              AND i18.var_name = i18_custom.var_name 
         WHERE
           i18.`lang` = "'.$db->escape($lang).'" 
            AND i18.var_name = "'.$db->escape($langVar).'" 
    ');
    if(empty($translation))
      $translation = '';
    $this->langStr[$lang][$langVar] = $translation;
    return $translation;
  }

  /**
   * @param array|string $varNames
   * @param string|null $lang
   */
  public function preloadVars($varNames, $lang = null) {
    $db = $this->db;

    if(empty($lang))
      $lang = $this->lang;

    if(is_array($varNames)) {
      $varNamesStr = '';
      foreach($varNames as $varName){
        if(!empty($varNamesStr))
          $varNamesStr .= ',';
        $varNamesStr .= '"' . $db->escape($varName) . '"';
      }
      $varNames = $varNamesStr;
      unset($varNamesStr);
    }else {
      $varNames = '"' . $db->escape($varNames) . '"';
    }

    $query = '
      SELECT
          ifnull(i18_custom.var_name, i18.var_name) as var_name,
          ifnull(i18_custom.translation, i18.translation) AS translation
        FROM i18
          LEFT JOIN i18_custom
            ON i18_custom.account_id = '.intval($this->accountId).'
              AND i18.lang = i18_custom.lang
              AND i18.var_name = i18_custom.var_name 
        WHERE
          i18.`lang` = "'.$db->escape($lang).'"
          AND i18.var_name in ('.$varNames.')
    ';

    $loadedVars = $db->queryAssocSimple($query);
    if(empty($this->langStr[$lang]))
      $this->langStr[$lang] = [];

    $this->langStr[$lang] = $loadedVars + $this->langStr[$lang];

  }

  /**
   * @param string $varName
   * @param null|string $lang
   * @return string
   */
  public function getPreloadedVar($varName, $lang = null) {
    if(empty($lang))
      $lang = $this->lang;
    return $this->langStr[$lang][$varName];
  }

  /**
   * @param array $translations
   * @param string $lang
   * @throws Exception
   */
  function updateMultiVar($translations, $lang = null) {
    $db = $this->db;
    $cache = $this->cache;

    if(empty($lang))
      $lang = $this->lang;

    $db->startTransaction();
    try {
      foreach($translations as $varName => $translation) {
        $cache->setExpired($this->getCacheLangName($varName, $lang));
        $row = [
          'lang' => $lang,
          'var_name' => $varName,
          'translation' => $translation,
        ];
        $db->simpleUpdate('i18', $row, ['lang', 'var_name']);
      }
      $db->commit();

      foreach($translations as $varName => $translation) {
        $this->langStr[$lang][$varName] = $translation;
        $cache->put($this->getCacheLangName($varName, $lang), $translation, self::$cacheTtl);
      }
    } catch (Exception $e) {
      $db->rollback();
      throw $e;
    }
  }

  /**
   * @param string $varName
   * @param array $translations
   * @throws Exception
   */
  function updateTranslations($varName, $translations) {
    $db = $this->db;
    $cache = $this->cache;

    $db->startTransaction();
    try {
      foreach($translations as $lang => $translation) {
        $cache->setExpired($this->getCacheLangName($varName, $lang));
        $row = [
          'lang' => $lang,
          'translation' => $translation,
        ];

        $id = $db->queryFirstField('
          SELECT id
            FROM
              i18
            WHERE
              `lang` = "'.$db->escape($lang).'"
              AND var_name = "' . $db->escape($varName) . '"
            FOR UPDATE'
        );

        if(empty($id)) {
          // not found - insert
          $row['var_name'] = $varName;
          $row['id'] = IdGen::getInstance()->nextId('i18', false);
          $db->insert('i18', $row);
        } else {
          $row['id'] = $id;
          $db->simpleUpdate('i18', $row, 'id'); // update by id
        }

      }

      $db->commit();
      foreach($translations as $lang => $translation) {
        $this->langStr[$lang][$varName] = $translation;
        $cache->put($this->getCacheLangName($varName, $lang), $translation, self::$cacheTtl);
      }
    } catch (Exception $e) {
      $db->rollback();
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function deleteTranslations($varNames) {

    $cache = $this->cache;
    $db = $this->db;

    if(is_string($varNames))
      $varNames = [$varNames];

    foreach($varNames as $varName) {
      $db->startTransaction();
      try {
        $db->query('DELETE FROM i18 WHERE var_name = "'.$db->escape($varName).'"');
        $db->commit();
        foreach(static::$languages as $lang) {
          unset($this->langStr[$lang][$varName]);
          $cache->setExpired($this->getCacheLangName($varName, $lang));
        }
      }catch (Exception $e) {
        $db->rollback();
        throw $e;
      }

    }
  }

  protected function _loadLanguage($lang) {
    // TODO: Implement _loadLanguage() method.
  }

  function addLangVar($varName, array $translations, $description) {
    // TODO: Implement addLangVar() method.
  }


}
