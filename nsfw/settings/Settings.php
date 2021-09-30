<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 28-04-16
 * Time: 10:59 AM
 */

namespace nsfw\settings;


interface Settings {
  /**
   * @param string $name
   * @param mixed|null $default
   * @return mixed
   */
  public function get($name, $default = null);

  /**
   * @param string $name
   * @param mixed $value
   */
  public function set($name, $value);

  /**
   * @param string $section
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public function getFromSection($section, $name, $default = null);

  /**
   * @param string $section
   * @param string $name
   * @param mixed $value
   */
  public function setInSection($section, $name, $value);
  /*
   * @return string
   */
  public function getSection();

  /**
   * @param string $section
   */
  public function setSection($section);

  /**
   * @return array
   */
  public function getAll();

  /**
   * @param array $settigns
   */
  public function setAll(array $settigns);

  /**
   * Store settigns, release handles
   */
  public function close();

}
