<?php

namespace nsfw\i18;


use nsfw\database\Database;
use nsfw\exception\FileNotFoundException;

class ArrayLanguage extends DbLanguage{
  /** @var array */
  protected $langStr = [];


  public function __construct() {
    parent::__construct(null);
  }


  /**
   * Imports language variables from PHP file. It must contain one global variable "translation" which must be an array
   * with the same format as $this->langStr.
   *
   * @param string $translationFile
   *
   * @throws FileNotFoundException
   */
  public function importFromFile($translationFile) {
    if(!is_file($translationFile))
      throw new FileNotFoundException($translationFile);
    ob_start();
    require $translationFile;
    ob_end_clean();
    /** @global $translation */
    $this->importFromArray($translation);
  }

  public function importFromArray(array $translation){
    foreach($translation as $lang=>$langArr) {
      foreach($langArr as $varName=>$value) {
        $this->langStr[$lang][$varName] = $value;
      }
    }

  }

  public function clearTranslations(){
    $this->langStr = [];
  }

  /**
   * If it's not already loaded return empty.
   *
   * @param $langVar
   * @param $lang
   * @return string
   */
  protected function loadVar($langVar, $lang) {
    return '';
  }

  function preloadVars($varNames, $lang = null) {
  }

  function updateMultiVar($translations, $lang) {
    foreach($translations as $var=>$text) {
      $this->langStr[$lang][$var] = $text;
    }
  }

  function updateTranslations($varName, $translations) {
    foreach($this->langStr as $lang=>$x) {
      foreach($translations as $text) {
        $this->langStr[$lang][$varName] = $text;
      }
    }
  }

  public function deleteTranslations($varNames) {
    foreach($this->langStr as $lang=>$x) {
      foreach($varNames as $varName) {
        if(isset($this->langStr[$lang][$varName]))
          unset($this->langStr[$lang][$varName]);
      }
    }
  }


//  public function translate($langVar, $lang = null) {
//    var_dump($this->langStr);
//    $translation = parent::translate($langVar, $lang);
//    echo "translating $langVar into $translation\n";
//    return $translation;
//  }


}
