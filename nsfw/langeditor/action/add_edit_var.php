<?php

namespace nsfw\langeditor\action;

use nsfw\controller\AbstractAction;
use nsfw\i18\DbLanguage;
use nsfw\i18\LangList;
use nsfw\langeditor\AddEditLangVarForm;
use nsfw\langeditor\LangAction;
use nsfw\template\CascadedTemplate;
use nsfw\template\HtmlEscapeProcessor;
use nsfw\template\UrlEncodeProcessor;

class add_edit_var extends LangAction {
  /** @var CascadedTemplate */
  private $tpl;

  /** @var AddEditLangVarForm */
  private $form;

  /** @var array */
  private $langs = [];

  function runEnd() {

    $mainTpl = $this->pageController->getTemplate();
    $this->tpl = $tpl = new CascadedTemplate();
    $tpl->loadFromFile('add_edit_var.html');
    $tpl->addProcessor(new UrlEncodeProcessor());
    $tpl->addProcessor(new HtmlEscapeProcessor());


    $ll = new LangList($this->db, $this->session);
    $this->langs = $langs = $ll->getLangs();
    $langVarName = getParam('varName','', 'GP');

    $trans = [];
    if(!empty($langVarName)) {
      $trans = $this->db->queryAssoc('
      SELECT `lang`, translation, description FROM i18
        WHERE var_name = "' . $langVarName . '"
    ', 'lang');
    }

    $this->form = $form = new AddEditLangVarForm('./add_edit_var.html', $this->langEditorUrl);
    $mainTpl->setVar('center', $form);
    $form->setTemplate($tpl);

    $form->getField('varName')->setValue($langVarName);
    $form->getField('orgVarName')->setValue($langVarName);


    $tplTranslations = [];
    $row = $tpl->getBlock('langRow');
    foreach($langs as $code => $lang) {

      $text = '';
      if(!empty($trans[$code]))
        $text = $trans[$code]['translation'];

      $form->addNewField($code, 'textarea', $text);

      if(!empty($trans['EN'])) {
        $form->getField('description')->setValue($trans['EN']['description']);
      }

    }
    $this->setTranslationsFromForm();



    if($form->processPost()) {
      $backUrl = $form->getField('bu')->value;
      foreach($langs as $code => $lang) {
        $ff = $form->getField($code);
        if(empty($ff))
          continue;
        $ff->getValue();
      }
      $this->setTranslationsFromForm();

      $data = $form->getData(['varName'=>'var_name']);
      $translations = $data;
      $id = $data['id'];
      $varName = $data['orgVarName'];
      $newVarName = $data['var_name'];
      $description = $data['description'];
      $id = $data['id'];
      //unset($translations['id'], $translations['var_name'], $translations['description'], $translations['bu']);
      $langs = DbLanguage::getLanguages();
      $translations = array_intersect_key($data, array_fill_keys($langs, true));
      $data = array_diff_key($data, $translations);
      $dbl = DbLanguage::getInstance();
      $dbl->updateTranslations($varName, $translations);

      $this->db->simpleUpdate('i18', ['lang'=>'EN', 'var_name' => $varName, 'description'=>$description], ['lang', 'var_name']);
      if($newVarName != $varName) {
        $dbl->purgeAllLangCache();
        $this->db->update('i18', ['var_name' => $newVarName], '`var_name` = "'.$this->db->escape($varName).'"');
      }
      httpRedirect($backUrl);
    }

  }

  private function setTranslationsFromForm() {
    $row = $this->tpl->getBlock('langRow');
    //$row->addProcessor(new HtmlEscapeProcessor());
    $p = $row->getProcessors();
    foreach($p as &$proc) {
      $proc = get_class($proc);
    }
    $row->clearRows();
    foreach($this->langs as $code=>$lang) {
      $ff = $this->form->getField($code);
      if(empty($ff))
        continue;
      $tplTranslation = [
        'language' => $lang,
        'langCode' => $code,
        'text' => $ff->getValue(),
      ];

      $row = $row->appendRow($tplTranslation);
      $row->setVar('text', $ff->getValue());
    }
  }


}
