<?php

namespace nsfw\template;


interface Template extends DisplayObject{

  /**
   * Loads template contents from file
   * @param string $filename
   */
  function loadFromFile($filename);

  /**
   * @param string $template
   */
  function setTemplate($template);

  /**
   * @return string
   */
  function getTemplate();

  /**
   * Alias for getHtml();
   * @return string
   */
  function getParsed();

  /**
   * does echo $this->getParsed()
   */
  function display(); // echo

  /**
   * @param string $blockName
   * @return bool
   */
  function hasBlock($blockName);
  /**
   * @param string $blockName
   * @return Template|bool
   */
  function getBlock($blockName);

  /**
   * @param $varName
   * @return bool
   */
  function hasVar($varName);
  /**
   * @param string $varName
   * @param string|DisplayObject $varValue
   */
  function setVar($varName, $varValue);
  /**
   * @param $varName
   * @return string|DisplayObject
   */
  function getVar($varName);

  /**
   * Resets the template clearing all the vars and blocks
   */
  function reset();

  /**
   * Set template vars. If there are any vars set prior call of this function they are REMOVED.
   *
   * @param array $vars
   */
  function setVars(array $vars);

  /**
   * Adds more vars to the template. Does not change existing ones, unless a var with the same name is in $vars param.
   * @param array $vars
   */
  function addVars(array $vars);
}
