<?php

namespace nsfw\i18;


use Exception;
use nsfw\cache\Cache;
use nsfw\database\Database;
use nsfw\database\IdGen;

/**
 * Class DbLanguage
 * @package nsfw\language
 *
 * @method static DbLanguage getInstance(Database $db = null, Cache $cache = null);
 * @method static DbLanguage newInstance(Database $db = null, Cache $cache = null);
 */
class DbLanguage extends SingeltonLanguage {
  /** @var array */
  protected $langStr = [];
  /** @var Database */
  protected $db;
  protected $table = 'i18';

  /**
   * DbLanguage constructor.
   *
   * @param Database $db
   * @param Cache|null $cache
   */
  public function __construct(Database $db = null, Cache $cache = null) {
    $this->db = $db;
    parent::__construct();
    if(!empty($cache))
      $this->cache = $cache;
  }

  /**
   * factory method used by Singelton class
   * @param Database $db
   * @param Cache|null $cache
   * @return static
   */
  public static function factory(Database $db, Cache $cache = null) {
    return new static($db, $cache);
  }

  public function loadLangList(){
    $db = $this->db;
    self::$languages = array_keys($db->queryAssocSimple('SELECT code, txt FROM lang_codes WHERE used = 1'));
  }

  protected function _loadLanguage($lang) {
    $db = $this->db;
    $langStr = $db->queryAssocSimple('
      SELECT var_name, translation FROM '.$db->escapeField($this->table).'
         WHERE
           `lang` = "'.$db->escape($lang).'" 
    ');

    return $langStr;
  }

  /**
   * Note: Relies on caller to start/commit transaction
   *
   * @param int|null $id
   * @param string $lang
   * @param string $varName
   * @param string $translation
   * @param string|null $description
   * @return int
   */
  private function insertUpdateLangRowExtTrans($id, $lang, $varName, $translation, $description = null) {
    $cache = $this->cache;
    $db = $this->db;

    $cache->setExpired($this->getCacheLangName($lang));
    $row = [
      'lang' => $lang,
      'var_name' => $varName,
      'translation' => $translation,
    ];

    if(!empty($id)) {
      $id = $db->queryFirstField('
          SELECT id
            FROM
              ' . $db->escapeField($this->table) . '
            WHERE
              `id` = ' . intval($id) . '
            FOR UPDATE'
      );
    }

    // if the function argument id is not empty, but here it's empty it means that the langvar was deleted in the middle
    // of code
    if(empty($id)) {
      $id = $db->queryFirstField('
          SELECT id
            FROM
              ' . $db->escapeField($this->table) . '
            WHERE
              `lang` = "' . $db->escape($lang) . '"
              AND var_name = "' . $db->escape($varName) . '"
            FOR UPDATE'
      );
    }

    if(empty($id)) {
      // not found - insert
      if(!is_null($description))
        $row['var_name'] = $varName;
        $row['id'] = IdGen::getInstance()->nextId($this->table, false);
      $db->insert($this->table, $row);
    } else {
      $row['id'] = $id;
      $db->simpleUpdate($this->table, $row, 'id'); // update by id
    }
    return $row['id'];
  }

  /**
   * @param int|null $id
   * @param string $lang
   * @param string $varName
   * @param string $translation
   * @param string|null $description
   */
  public function insertUpdateLangVar($id, $lang, $varName, $translation, $description = null) {
    $this->db->startTransaction();
    try {
      $this->insertUpdateLangRowExtTrans($id, $lang, $varName, $translation, $description);
      $this->db->commit();
    }catch (Exception $e) {
      $this->db->rollback();
    }
  }

  /**
   * Same as insertUpdateLangVar, but parameters are in array
   *
   * @param array $row
   *
   * int|null 'id'
   * string 'lang'
   * string 'var_name'
   * string 'translation'
   * string|null 'description'
   * @throws Exception
   */
  public function insertUpdateLangRow(array $row) {
    if(empty($row['description']))
      $row['description'] = null;
    $this->db->startTransaction();
    try {
      $this->insertUpdateLangRowExtTrans($row['id'], $row['lang'], $row['var_name'], $row['translation'], $row['description']);
      $this->db->commit();
    }catch (Exception $e) {
      $this->db->rollback();
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Updates single language translations of multiple vars
   *
   * @param array $translations format: [ varName => translation ]
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
      $this->purgeCache($lang);
      foreach($translations as $varName => $translation) {
        $row = [
          'lang' => $lang,
          'var_name' => $varName,
          'translation' => $translation,
        ];
        $db->simpleUpdate($this->table, $row, ['lang', 'var_name']);
      }
      $db->commit();

      foreach($translations as $varName => $translation) {
        $this->langStr[$lang][$varName] = $translation;
      }
      $this->storeLangToCache($lang);
    } catch (Exception $e) {
      $db->rollback();
      throw $e;
    }
  }

  /**
   * Updates single translations of multiple vars by id
   *
   * @param array $translations format: [ id => translation ]
   * @throws Exception
   */
  function updateMultiVarById($translations) {
    $db = $this->db;
    $cache = $this->cache;

    $db->startTransaction();
    try {
      $this->purgeAllLangCache();
      foreach($translations as $id => $translation) {
        $row = [
          'id' => $id,
          'translation' => $translation,
        ];
        $db->simpleUpdate($this->table, $row, 'id');
      }
      $db->commit();

      $this->langStr = []; // reload is required
    } catch (Exception $e) {
      $db->rollback();
      throw $e;
    }
  }

  /**
   * Updates all translations for single lang var.
   *
   * @param string $varName
   * @param array $translations  Array with following data [<lang code> => <translation>]
   * @throws Exception
   */
  function updateTranslations($varName, $translations) {
    $db = $this->db;
    $cache = $this->cache;

    $db->startTransaction();
    try {
      $this->purgeAllLangCache();
      foreach($translations as $lang => $translation) {
        $row = [
          'lang' => $lang,
          'translation' => $translation,
        ];
        $this->updateInternalVar($lang, $varName, $translation);

        $id = $db->queryFirstField('
          SELECT id
            FROM
              '.$db->escapeField($this->table).'
            WHERE
              `lang` = "'.$db->escape($lang).'"
              AND var_name = "' . $db->escape($varName) . '"
            FOR UPDATE'
        );

        if(empty($id)) {
          // not found - insert
          $row['var_name'] = $varName;
          $row['id'] = IdGen::getInstance()->nextId($this->table, false);
          $db->insert($this->table, $row);
        } else {
          $row['id'] = $id;
          $db->simpleUpdate($this->table, $row, 'id'); // update by id
        }

      }

      $db->commit();
      $this->storeAllLangsToCache();
    } catch (Exception $e) {
      $db->rollback();
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * @param string $lang
   * @param string $varName
   * @param bool $forUpdate
   */
  private function getDbLangVar($lang, $varName, $forUpdate = false) {
    $db = $this->db;
    $addon = '';
    if($forUpdate)
      $addon .= ' FOR UPDATE';
    $enId = $db->queryFirstField('
        SELECT * FROM '.$db->escapeField($this->table).'
          WHERE
            `lang` = "'.$db->escape('EN').'"
            AND var_name = "'.$db->escape($varName).'"
          
           '.$addon);

  }

  /**
   * @param string $lang
   * @param string $varName
   * @param bool $forUpdate
   */
  private function getDbLangVarId($lang, $varName, $forUpdate = false) {
    $db = $this->db;
    $addon = '';
    if($forUpdate)
      $addon .= ' FOR UPDATE';
    $enId = $db->queryFirstField('
        SELECT id FROM '.$db->escapeField($this->table).'
          WHERE
            `lang` = "'.$db->escape('EN').'"
            AND var_name = "'.$db->escape($varName).'"
          
           '.$addon);

  }

  /**
   * Creates new language var. Var is added to database and loaded languages
   *
   * @param string $varName
   * @param array $translations translations in format [ langCode => translation ]
   * @param string $description
   * @throws Exception
   */
  public function addLangVar($varName, array $translations, $description) {
    $db = $this->db;
    $cache = $this->cache;
    $idGen = IdGen::getInstance();

    $db->startTransaction();
    try {
      $this->purgeAllLangCache();
      foreach(self::$languages as $lang) {
        $id = $this->getDbLangVarId($lang, $varName);
        $row = [
          'id' => $id,
          'lang'=>$lang,
          'var_name' => $varName,
          'translation' => null,
          'description' => '',
        ];
        if(!empty($translations[$lang]))
          $row['translation'] = $translations[$lang];
        if($lang == 'EN')
          $row['description'] = $description;


        if(empty($id)) {
          // not found - insert
          $row['var_name'] = $varName;
          $row['id'] = IdGen::getInstance()->nextId($this->table, false);
          $db->insert($this->table, $row);
        } else {
          $row['id'] = $id;
          $db->simpleUpdate($this->table, $row, 'id'); // update by id
        }
        $this->updateInternalVar($lang, $varName, $row['translation']);

      }

      $db->commit();

      $this->storeAllLangsToCache();

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

    $this->purgeAllLangCache();
    foreach($varNames as $varName) {
      $db->startTransaction();
      try {
        $db->query('DELETE FROM '.$db->escapeField($this->table).' WHERE var_name = "'.$db->escape($varName).'"');
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
    $this->storeAllLangsToCache();
  }


}
