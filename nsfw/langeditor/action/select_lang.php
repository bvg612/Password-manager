<?php
/**
 * User: npelov
 * Date: 03-07-17
 * Time: 5:52 PM
 */

namespace nsfw\langeditor\action;


use nsfw\i18\LangList;
use nsfw\langeditor\LangAction;
use nsfw\uri\Url;

class select_lang extends LangAction {
  function runEnd() {
    $bu = new Url(getParam('bu', $this->langEditorUrl, ''));
    $bu->removeParam('bu');

    $refLang = getParam('refLang', false);
    $editLang = getParam('editLang', false);
    if(!$refLang || !$editLang)
      httpRedirect(strval($bu));

    $ll = new LangList($this->db, $this->session);


    if(!$ll->isValidLang($refLang) || !$ll->isValidLang($editLang)) {
      httpRedirect(strval($bu));
    }

    try {
      $ll->setRefLang($refLang);
      $ll->setEditLang($editLang);
    } catch (\OutOfRangeException $e) {
      httpRedirect(strval($bu));
    }
    httpRedirect(strval($bu));
  }



}
