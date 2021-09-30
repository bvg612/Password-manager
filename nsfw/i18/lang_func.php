<?php


/**
 * This is getter and setter for lanugage object that's used by language functions
 * @param Language|null $langObj if this is not empty language object will be set
 * @return Language
 */
function nsfwLanguageObject($langObj = null) {
  static $object;
  if(!empty($langObj))
    $object = $langObj;
  return $object;
}

function t($langVar) {
  /** @var \nsfw\i18\Language $langObj */
  $langObj = nsfwLanguageObject();
  return $langObj->translate($langVar);
}

function tf($langVar) {
  /** @var \nsfw\i18\Language $langObj */
  $langObj = nsfwLanguageObject();
  return $langObj->translateUFirst($langVar);
}

function tw($langVar) {
  /** @var \nsfw\i18\Language $langObj */
  $langObj = nsfwLanguageObject();
  return $langObj->translateUWords($langVar);
}

function tu($langVar) {
  /** @var \nsfw\i18\Language $langObj */
  $langObj = nsfwLanguageObject();
  return $langObj->translateUpper($langVar);
}

function tl($langVar) {
  /** @var \nsfw\i18\Language $langObj */
  $langObj = nsfwLanguageObject();
  return $langObj->translateLower($langVar);
}
