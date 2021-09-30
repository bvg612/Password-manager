<?php

namespace nsfw\i18;

require_once __DIR__ . '/lang_func.php';


interface Language {

  /**
   * @param string|array $langs Language(s) to remove
   */
  public static function removeLanguages($langs);

  /**
   * @param string|array $langs Language(s) to add
   */
  public static function addLanguages($langs);

  /**
   * Return languages - array of two letter language codes
   *
   * @return array
   */
  public static function getLanguages();

  /**
   * @return string Returns the current language
   */
  function getLang();

  /**
   * @param string $lang
   */
  function setLang($lang);

  /**
   * @return string
   */
  function getEncoding();

  /**
   * @param string $encoding
   */
  function setEncoding($encoding);

  /**
   * @param string|null $lang Two letter language code. If set loads this language instead
   * @return bool true on success, false on failure
   */
  public function loadLanguage($lang = null);

  /**
   * @param string $langVar
   * @param string|null $lang
   * @return string
   */
  function translate($langVar, $lang = null);

  /**
   * @param string $langVar
   * @param string|null $lang
   * @return string
   */
  function translateUFirst($langVar, $lang = null);

  /**
   * @param string $langVar
   * @param string|null $lang
   * @return string
   */
  function translateUWords($langVar, $lang = null);

  /**
   * @param string $langVar
   * @param string|null $lang
   * @return string
   */
  function translateUpper($langVar, $lang = null);

  /**
   * @param string $langVar
   * @param string|null $lang
   * @return string
   */
  function translateLower($langVar, $lang = null);

  /**
   * Updates multiple variable translations for single language
   *
   * @param array $translations Array of translations with variable names as key, translation string as a value
   * @param string $lang
   */
  function updateMultiVar($translations, $lang);

  /**
   * Updates all language translations for specific var
   *
   * @param string $varName language variable name
   * @param array $translations Array of translation strings with language as key
   */
  function updateTranslations($varName, $translations);

  /**
   * @param string $varName
   * @param array $translations translations in format [ langCode => translation ]
   * @param string $description
   */
  function addLangVar($varName, array $translations, $description);

    /**
   * Delete translations by varName
   *
   * @param string|array $varNames A varname or array of varnames
   */
  public function deleteTranslations($varNames);
}
