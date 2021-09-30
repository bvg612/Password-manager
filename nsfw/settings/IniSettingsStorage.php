<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 28-04-16
 * Time: 11:15 AM
 */

namespace nsfw\settings;


use Exception;
use nsfw\exception\FileNotFoundException;

class IniSettingsStorage implements SettingsStorage {
  protected $iniFile;

  /**
   * IniSettingsStorage constructor.
   */
  public function __construct($iniFile) {
    $this->iniFile = $iniFile;
  }

  public function getLoadMethod() {
    return SettingsStorage::LOAD_ALL;
  }

  public function store(Settings $settings) {
    // TODO: Implement store() method - save settings into an ini file
  }

  public function load(Settings $settings) {
    $settings = @parse_ini_file($this->iniFile, true);
    if($settings === false)
      throw new FileNotFoundException($this->iniFile);
    return $settings;
  }

  public function loadOne($name) {
    throw new Exception('Cannot store/load one value. Use store()/load() instead!');
  }

  public function storeOne($name, $value) {
    throw new Exception('Cannot store/load one value. Use store()/load() instead!');
  }


}
