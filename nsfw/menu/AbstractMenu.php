<?php

namespace nsfw\menu;


use nsfw\template\CascadedTemplate;

abstract class AbstractMenu implements Menu{

  /** @var array */
  protected $items = [];
  /** @var CascadedTemplate */
  protected $tpl;
  protected $menuItemBlock = "menuItem";
  protected $class = "normal";

  /**
   * AbstractMenu constructor.
   */
  public function __construct() {
    $this->tpl = new CascadedTemplate();
  }

  /**
   * @return string
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * @param string $class
   */
  public function setClass($class) {
    $this->class = $class;
  }

  /**
   * @return string
   */
  public function getMenuItemBlock() {
    return $this->menuItemBlock;
  }

  /**
   * @param string $menuItemBlock
   */
  public function setMenuItemBlock($menuItemBlock) {
    $this->menuItemBlock = $menuItemBlock;
  }


  public function add(MenuItem $item) {
    $this->items[] = $item;
  }

  public function addItem($title, $link) {
    $this->items[] = $item = new SimpleMenuItem($title, $link);
    return $item;
  }


  /**
   * @param CascadedTemplate|string $tpl
   */
  public function setTemplate($tpl) {
    if($tpl instanceof CascadedTemplate) {
      $this->tpl = $tpl;
      return;
    }
    $this->tpl = new CascadedTemplate($tpl);
  }

  public function setTemplateFile($tplFile) {
    $this->tpl->loadFromFile($tplFile);
  }

  public function apply() {
    $tpl = $this->tpl;
    foreach($this->items as $item) {
      /** @var AbstractMenuItem $item */
      $item->applyToBlock($tpl->getBlock($this->menuItemBlock));
    }
  }

  function getHtml() {
    $tpl = $this->tpl;
    foreach($this->items as $item) {
      /** @var AbstractMenuItem $item */
      $item->applyToBlock($tpl->getBlock($this->menuItemBlock));
    }
    $tpl->setVars([
      'class', $this->class
    ]);
    return $tpl->getParsed();
  }

  function __toString() {
    return $this->getHtml();
  }

}
