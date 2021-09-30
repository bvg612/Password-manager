<?php
/**
 * User: npelov
 * Date: 06-07-17
 * Time: 8:28 PM
 */

namespace nsfw\langeditor;

class LangEditorData {
  /** @var int id of edit lang var */
  public $id;
  /** @var string */
  public $langVar;
  /** @var string */
  public $refText = '';
  /** @var string */
  public $editText = '';
  /** @var string */
  public $description = '';

  public function setRefData(LangVar $langVar) {
    if(empty($langVar))
      return;
    $this->langVar = $langVar->name;
    $this->refText = $langVar->translation;
    $this->description = $langVar->description;
  }

  public function setEditData(LangVar $langVar) {
    if(empty($langVar))
      return;
    $this->id = $langVar->id;
    $this->editText = $langVar->translation;
    if(empty($this->editText))
      $this->editText = '';
  }
}
