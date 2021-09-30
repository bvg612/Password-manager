<?php
/**
 * User: npelov
 * Date: 23-06-17
 * Time: 11:49 AM
 */

namespace nsfw\langeditor\action;


use nsfw\database\display\ListTable;
use nsfw\i18\DbLanguage;
use nsfw\i18\LangList;
use nsfw\langeditor\FilterForm;
use nsfw\langeditor\LangAction;
use nsfw\langeditor\Language;
use nsfw\langeditor\Template;
use nsfw\template\CascadedTemplate;
use nsfw\template\HtmlEscapeProcessor;
use nsfw\template\UrlEncodeProcessor;
use nsfw\uri\Url;

/**
 * Class index
 * @package nsfw\langeditor\action
 *
 */
class index extends LangAction {

  /** @var LangList */
  private $ll;
  /** @var string */
  private $refLangCode;
  /** @var string */
  private $editLangCode;
  /** @var CascadedTemplate */
  private $tpl;

  /** @var FilterForm */
  private $filterForm;

  /** @var DbLanguage */
  private $language;

  /**
   * index constructor.
   */
  public function __construct() {
    parent::__construct();
  }


  private function initLangAndFilterForms() {
    $ll = $this->ll;
    $this->tpl->setVar('refLangOptions', $ll->getSelectOptions($this->refLangCode));
    $this->tpl->setVar('editLangOptions', $ll->getSelectOptions($this->editLangCode));
    $ff = $this->filterForm = new FilterForm();
  }

  private function processForm() {
    if(strtolower($_SERVER['REQUEST_METHOD']) != 'post')
      return;
    $backUrl = getParam('bu', $this->langEditorUrl, 'PG');
    $translations = getParam('tr', [], 'P');
    if(!empty($translations))
      $this->language->updateMultiVarById($translations);
    httpRedirect($backUrl);
  }

  public function processFilters() {
    $ff = $this->filterForm;
    if(!$ff->processPost())
      return;

    $backUrl = getParam('bu', $this->langEditorUrl, 'PG');

    $showOnlyEmpty = $ff->getField('showOnlyEmpty')->isChecked();
    setcookie('f_se', $showOnlyEmpty?1:0, time()+365*24*3600,'/');

    httpRedirect('./');
  }

  function runEnd() {

    $mainTpl = $this->pageController->getTemplate();
    //$this->createMainTemplate('list.html');
    $this->tpl = $tpl = $this->createCenterTemplate('list.html');
    $tpl->getBlock('buttons')->show();

    $this->ll = $ll = new LangList($this->db, $this->session);
    DbLanguage::removeLanguages('en');
    DbLanguage::addLanguages(array_keys($ll->getLangs()));
    $this->language = DbLanguage::getInstance($this->db);

    $this->refLangCode = $ll->getRefLang();
    $this->editLangCode = $ll->getEditLang();

    $this->initLangAndFilterForms();

    $tpl->setVar('refLang', $ll->getLangName($this->refLangCode));
    $tpl->setVar('editLang', $ll->getLangName($this->editLangCode));

    $bu = new Url();
    $bu->removeParam('bu');
    $tpl->setVar('bu', $bu->getUri());


    $refLanguage = new Language($this->db, $this->refLangCode, $this->language);
    if($this->refLangCode == $this->editLangCode)
      $editLanguage = $refLanguage;
    else
      $editLanguage = new Language($this->db, $this->editLangCode, $this->language);


    if($this->refLangCode == 'EN') {
      $descrLanguage = $refLanguage;
    } else if($this->editLangCode == 'EN') {
      $descrLanguage = $editLanguage;
    } else {
      $descrLanguage = new Language($this->db, 'EN', $this->language);
    }

    $refLanguage->setOnlyEmpty($this->filterForm->showOnlyEmpty->isChecked());
    $editorRows = $refLanguage->getEditorRows($editLanguage, $descrLanguage);

    $this->processFilters();

    $this->processForm();
    $this->filterForm->setTemplate($tpl->filterForm);
    $tpl->filterForm->show();
    $this->filterForm->getHtml();

    $lt = new ListTable($tpl->getBlock('langRow'), $editorRows);
    $lt->apply();

  }

}
