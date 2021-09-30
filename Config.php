<?php

use nsfw\GenericConfig;

/**
 * Class Config
 *
 * @property string $iniFile
 * @method static \Config getInstance()
 * @method static \Config newInstance()
 */
class Config extends \nsfw\Config{
  public function __construct(GenericConfig $ac = null) {
    parent::__construct($ac);
    $this->webHost  = 'nsfw3.linux.loc';
    $this->addVars([
      'iniFile',
      'jsDevelop',
    ]);

  }

}
