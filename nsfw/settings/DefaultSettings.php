<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 28-04-16
 * Time: 11:01 AM
 */

namespace nsfw\settings;


use Exception;

class DefaultSettings implements Settings{
  protected $section = 'global';
  protected $settings = [
    'global' => '',
  ];
  protected $storage;

  /**
   * AbstractSettings constructor.
   */
  public function __construct(SettingsStorage $storage) {
    $this->storage = $storage;
    if($storage->getLoadMethod() == SettingsStorage::LOAD_ALL)
      $this->settings = $storage->load($this);
  }

  function get($name, $default = null) {
    return $this->getFromSection($this->section, $name, $default);
  }

  public function set($name, $value) {
    $this->setInSection($this->section, $name, $value);
  }

  public function getSection() {
    return $this->section;
  }

  public function setSection($section) {
    assert(is_string($section));
    $this->section = $section;
  }

  public function getAll() {
    return $this->settings;
  }

  public function setAll(array $settings) {
    $this->settings = $settings;
  }

  /**
   * @param string $section
   * @param string $name
   * @param mixed $default
   * @return mixed
   * @throws Exception
   */
  public function getFromSection($section, $name, $default = null) {
    if(!array_key_exists($name, $this->settings)) {
      if($this->storage->getLoadMethod() == SettingsStorage::LOAD_ONE_BY_ONE)
        $this->settings[$section][$name] = $this->storage->loadOne($name);
    }

    if(!isset($this->settings[$section]))
      throw new Exception('section '.$section.' does not exist.');
    if(!array_key_exists($name, $this->settings[$section]))
      throw new Exception('setting with name '.$name.' does not exist.');


    $value = $this->settings[$section][$name];

    $config = getConfig();
    $value = str_replace('{%PROJECT_ROOT}', $config->projectRoot, $value);
    return $value;
  }

  /**
   * @param string $section
   * @param string $name
   * @param mixed $value
   */
  public function setInSection($section, $name, $value) {
    $this->settings[$section][$name] = $value;

    if($this->storage->getLoadMethod() == SettingsStorage::LOAD_ONE_BY_ONE)
      $this->storage->storeOne($name, $value);
  }

  public function close() {
    if($this->storage->getLoadMethod() == SettingsStorage::LOAD_ALL)
      $this->storage->store($this);
  }

}
