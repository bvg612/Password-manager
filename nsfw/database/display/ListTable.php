<?php

namespace nsfw\database\display;


use Exception;
use nsfw\template\AbstractDisplayObject;
use nsfw\template\CascadedTemplate;
use nsfw\template\DisplayObject;
use nsfw\template\Template;

/**
 * Class ListTable
 * Formats database result array (returned by queryRows). Can be used to convert database rows to HTML, CSV or XML
 *
 * @package nsfw\database\display
 */
class ListTable extends AbstractDisplayObject{
  /** @var array */
  protected $rows;
  /** @var CascadedTemplate */
  protected $tpl;

  /** @var array */
  protected $nameMap = [];

  /** @var array */
  protected $filters = [];

  protected $userData = [];

  /**
   * ListTable constructor.
   * @param string|CascadedTemplate $template Template file name or template object
   * @param array $rows Array returned
   * @param array $nameMap
   * @throws Exception
   */
  public function __construct($template, array $rows = null, $nameMap = []) {
    $this->nameMap = $nameMap;

    if(is_string($template)) {
      $tpl = new CascadedTemplate();
      $tpl->loadFromFile($template);
    } else {
      if($template instanceof Template) {
        $this->tpl = $template;
      } else {
        throw new Exception("Second argument must be string (template file name) or instance of Template");
      }
    }
    if(!empty($rows)) {
      $this->rows = $rows;
    }
  }

  public function setUserData($name, $data) {
    $this->userData[$name] = $data;
  }

  public function getUserData($name) {
    return $this->userData[$name];
  }

  /**
   * @return array
   */
  public function getRows() {
    return $this->rows;
  }

  /**
   * @param array $rows
   */
  public function setRows(array $rows) {
    $this->rows = $rows;
  }

  /**
   * @param array|object $row
   * @return array
   */
  protected function getRowVars($row){
    $vars = [];
    foreach($row as $name=>$value) {
      $varIndex = $name;
      if(array_key_exists($name, $this->nameMap))
        $varIndex = $this->nameMap[$varIndex];
      if(array_key_exists($varIndex, $this->filters))
        $value = call_user_func($this->filters[$varIndex], $value, $row, $this);
      $vars[$varIndex] = $value;
    }
    return $vars;
  }

  /**
   * @param string $fieldName
   * @param callable $filter function with two parameters - $value, $row. Must return string result
   */
  public function addFilter($fieldName, callable $filter){
    $this->filters[$fieldName] = $filter;
  }

  public function apply() {
    if(!is_array($this->rows) || empty($this->rows))
      return;
    foreach($this->rows as $row) {
      $this->tpl->appendRow($this->getRowVars($row));
    }
  }

  public function getHtml() {
    $this->apply();
    return $this->tpl->getParsed();
  }

}
