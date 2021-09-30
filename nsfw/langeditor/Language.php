<?php
/**
 * User: npelov
 * Date: 06-07-17
 * Time: 7:52 PM
 */

namespace nsfw\langeditor;


use nsfw\database\Database;
use nsfw\database\Databasei;
use nsfw\i18\DbLanguage;

class Language {
  /** @var Databasei */
  protected $db;

  /** @var array */
  protected $langVars;

  /** @var string */
  protected $langCode;

  /** @var DbLanguage */
  protected $language;

  protected $onlyEmpty = false;

  /**
   * Language constructor.
   * @param Database $db
   * @param string $lang
   * @param $language
   */
  public function __construct(Database $db, $lang, $language) {
    $this->db = $db;
    $this->langCode = $lang;
    $this->language = $language;
    $this->load();
  }

  /**
   * @return string
   */
  public function getLang() {
    return $this->langCode;
  }

  /**
   * @param string $langCode
   */
  public function setLang($langCode) {
    $this->langCode = $langCode;
  }

  /**
   * @return bool
   */
  public function isOnlyEmpty() {
    return $this->onlyEmpty;
  }

  /**
   * @param bool $onlyEmpty
   */
  public function setOnlyEmpty($onlyEmpty) {
    $this->onlyEmpty = $onlyEmpty;
  }



  public function load() {
    $db = $this->db;
    $rows = $db->queryRows('SELECT id, var_name, translation, description FROM i18 WHERE lang = "'.$db->escape($this->langCode).'"', Database::MYSQL_OBJECT, 'nsfw\\langeditor\\LangVar');
    $this->langVars = [];
    foreach($rows as $langVar) {
      //$langVar = new LangVar($row);
      //var_dump($row, $langVar);exit;
      $this->langVars[$langVar->name] = $langVar;
    }
  }

  public function getEditorRows(Language $editLang, Language $descrLang) {
    $editLangData = $editLang->getData();
    $descrLangData = $descrLang->getData();
    $editorRows = [];
    foreach($this->langVars as $langVar) {
      /** @var LangVar $langVar */
      $name = $langVar->name;
      if($this->onlyEmpty && !empty($editLangData[$name]->translation)) {
        continue;
      }
      $data = new LangEditorData();
      $data->setRefData($langVar);
      if(!array_key_exists($name, $editLangData)) {
        $row = [
          'id' => null,
          'lang' => $editLang->getLang(),
          'var_name' => $name,
          'translation' => null,
          'description' => '',
        ];
        $row['id'] = $this->language->insertUpdateLangRow($row);
        /*
        $row['id'] = $this->db->insertIgnore('i18', $row);
        if(!$row['id']) {
          $row['id'] = $this->db->queryFirstField('SELECT id FROM i18 WHERE lang - "'.$this->db->escape($editLang->getLang()).'" AND var_name = "'.$this->db->escape($name).'"');
        }
        */

        $editLangData[$name] = new LangVar($row);
      }
      $data->setEditData($editLangData[$name]);
      if(!empty($descrLangData[$name]))
        $data->description = $descrLangData[$name]->description;
      $editorRows[$name] = $data;
    }
    return $editorRows;
  }

  public function getData() {
    return $this->langVars;
  }

}
