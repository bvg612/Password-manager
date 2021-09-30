<?php
/**
 * User: npelov
 * Date: 02-07-17
 * Time: 6:20 PM
 */

namespace nsfw\i18;


use nsfw\database\Database;
use nsfw\session\Session;
use OutOfRangeException;

class LangList {
  const refLangSessionVar = 'rl';
  const editLangSessionVar = 'el';
  /** @var Database */
  protected $db;
  /** @var Session */
  protected $session;
  /** @var array */
  protected $langs;
  /** @var string */
  protected $refLang;
  /** @var string */
  protected $editLang;

  protected $defaultRefLang;
  protected $defaultEditLang;

  /**
   * LangList constructor.
   * @param Database $db
   * @param Session $session
   */
  public function __construct(Database $db, Session $session) {
    $this->db = $db;
    $this->session = $session;
    $this->load();
    $this->defaultRefLang = 'EN';
    $this->defaultEditLang = 'EN';
    reset($this->langs);
    if(!array_key_exists($this->defaultRefLang, $this->langs))
      $this->defaultRefLang = key($this->langs);
    if(count($this->langs)>1) {
      next($this->langs);
      if(!array_key_exists($this->defaultEditLang, $this->langs))
        $this->defaultEditLang = key($this->langs);
    } else {
      $this->defaultEditLang = $this->defaultRefLang;
    }
  }

  /**
   * @return array
   */
  public function getLangs() {
    return $this->langs;
  }

  public function load() {
    $this->langs = $this->db->queryAssocSimple('SELECT code, txt FROM lang_codes WHERE used = 1');
  }

  /**
   * @param string $str
   * @return string
   */
  public static function changeCase($str) {
    return strtoupper($str);
  }

  public function isValidLang($lang) {
    return array_key_exists($lang, $this->langs);
  }

  public function getLangName($langCode) {
    return $this->langs[$langCode];
  }

  /**
   * @return string
   */
  public function getRefLang() {
    $lang = self::changeCase($this->session->get(self::refLangSessionVar, $this->defaultRefLang));
    reset($this->langs);
    if(!array_key_exists($lang, $this->langs))
      $lang = key($this->langs);
    return $lang;
  }

  /**
   * @param string $refLang
   * @throws OutOfRangeException
   */
  public function setRefLang($refLang) {
    $lang = self::changeCase($refLang);

    if(!array_key_exists($lang, $this->langs))
      throw new OutOfRangeException('Lang '.$lang.' does not exist.');

    $this->refLang = $lang;
    $this->session->set(self::refLangSessionVar, $this->refLang);
  }

  /**
   * @return string
   */
  public function getEditLang() {
    $lang = self::changeCase($this->session->get(self::editLangSessionVar, $this->defaultEditLang));
    reset($this->langs);
    if(!array_key_exists($lang, $this->langs))
      $lang = key($this->langs);
    $this->editLang = $lang;
    return $this->editLang;
  }

  /**
   * @param string $editLang
   * @throws OutOfRangeException
   */
  public function setEditLang($editLang) {
    $lang = self::changeCase($editLang);

    if(!array_key_exists($lang, $this->langs))
      throw new OutOfRangeException('Lang '.$lang.' does not exist.');

    $this->editLang = $lang;
    $this->session->set(self::editLangSessionVar, $this->editLang);
  }

  /**
   * @param null $selected
   * @return string
   * @internal param null|string $selectedHtml
   */
  public function getSelectOptions($selected = null) {
    $options = '';
    foreach($this->langs as $code=>$lang) {
      $selectedHtml = '';
      if($selected == $code)
        $selectedHtml = ' selected="selected"';
      $options .= '<option value="'.$code.'"'.$selectedHtml.'>'.$lang.'</option>';
    }
    return $options;
  }


}
