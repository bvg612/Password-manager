<?php
/**
 * User: npelov
 * Date: 13-05-17
 * Time: 1:54 PM
 */

namespace action;


use nsfw\controller\ActionInitializer;

use nsfw\template\CascadedTemplate;

class initialize extends ActionInitializer{
  /** @var CascadedTemplate */
  protected $mainTpl;

  protected function getExcludePaths() {
    return ['/admin/', '/w/', '/m/', '/le/'];
  }

  public function init() {
    $this->mainTpl = $mainTpl = new CascadedTemplate();
    $mainTpl->loadFromFile('index.html');
//    if($_SERVER['HTTP_HOST'] == 'inv.nicksoft.info')
//      $mainTpl->getBlock('devlabel')->show();
    $this->pageController->setTemplate($mainTpl);
  }


  public function initCommon() {
    $ns = ns();

    $pc = $this->pageController;
//    $ns->includeJsFiles($pc, 'js/common.js', $ns->jsFiles);
    $ns->includeJsFiles($pc, '/js/ns.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/timer.js', $ns->jsFiles);
    $ns->includeJsFiles($pc, '/js/site.js', $ns->jsFiles);
    $ns->includeJsFiles($pc, '/js/seedrandom.min.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/ajax.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/forms.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/invoice.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/invoice_item.js', $ns->jsFiles);
//    $ns->includeJsFiles($pc, '/js/CheckboxSelector.js', $ns->jsFiles);
    $pc->addCssFile('/tpl/css/style.css');
    $pc->addCssFile('/tpl/css/button.css');
//    $pc->addCssFile('/tpl/css/radio.css');
//    $pc->addJsFile('/js/ns.js');
//    $pc->addJsFile('/js/site.js');
  }

}
