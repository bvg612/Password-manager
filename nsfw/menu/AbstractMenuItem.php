<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 27-05-16
 * Time: 6:05 PM
 */

namespace nsfw\menu;


use Exception;
use nsfw\template\CascadedTemplate;

abstract class AbstractMenuItem implements MenuItem {
  protected $children = [];
  /** @var AbstractMenu */
  protected $submenu = null;

  /** @var string */
  protected $link;
  /** @var string */
  protected $title;
  /** @var string */
  protected $cssClass;

  /**
   * AbstractMenuItem constructor.
   * @param string $title
   * @param string $link
   * @param AbstractMenu $submenu
   */
  public function __construct($title, $link, AbstractMenu $submenu = null) {
    $this->link = $link;
    $this->title = $title;
    $this->submenu = $submenu;
  }

  abstract public function isSelected();

  /**
   * @return string
   */
  public function getCssClass() {
    return $this->cssClass;
  }

  /**
   * @param string $cssClass
   */
  public function setCssClass($cssClass) {
    $this->cssClass = $cssClass;
  }

  /**
   * @return array
   */
  public function getChildren() {
    return $this->children;
  }

  public function applyToBlock(CascadedTemplate $block) {
    $cssClass = $this->cssClass;
    if ($this->isSelected())
      $cssClass .= ' selected';
    $row = $block->appendRow([
      'link' => $this->link,
      'title' => $this->title,
      'class' => $cssClass,
    ]);

    $submenuTpl = $row->getBlock('submenu');

    if(empty($this->submenu)) {
      if(!$submenuTpl)
        return;
      $submenuTpl->hide();
      return;
    }

    if(empty($submenuTpl))
      throw new \RuntimeException("submenu not found in ".$row->getName().' block.'.$row->getTemplate());


    $submenuTpl->show();
    $this->submenu->setTemplate($submenuTpl);
    $this->submenu->apply();

  }



}
