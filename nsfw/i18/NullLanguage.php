<?php

namespace nsfw\i18;


class NullLanguage extends AbstractLanguage{
  public function translate($langVar, $lang = null) {
    return $langVar;
  }

  protected function _loadLanguage($lang) {
    return [];
  }

  function updateMultiVar($translations, $lang) {
  }

  function updateTranslations($varName, $translations) {
  }

  public function deleteTranslations($varNames) {
  }

  function addLangVar($varName, array $translations, $description) {
  }


}
