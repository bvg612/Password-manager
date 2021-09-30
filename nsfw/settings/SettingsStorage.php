<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 28-04-16
 * Time: 11:08 AM
 */

namespace nsfw\settings;


interface SettingsStorage {
  const LOAD_ONE_BY_ONE = 1;
  const LOAD_ALL = 2;

  /**
   * @return int returns one of LOAD_ constants
   */
  public function getLoadMethod();
  public function store(Settings $settings);
  public function load(Settings $settings);
  public function loadOne($name);
  public function storeOne($name, $value);
}
