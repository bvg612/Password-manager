<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 09-05-16
 * Time: 12:06 PM
 */

namespace nsfw\settings;


class IniSettings extends DefaultSettings{
  /**
   * IniSettings constructor.
   * @param string $file
   */
  public function __construct($file) {
    parent::__construct(new IniSettingsStorage($file));
  }

}
