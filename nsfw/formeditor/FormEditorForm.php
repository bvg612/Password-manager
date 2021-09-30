<?php

namespace nsfw\formeditor;

use nsfw\Config;
use nsfw\forms\AbstractForm;
use nsfw\forms\FormLoader;
use nsfw\forms\XmlFormLoader;

/**
 * Class FormEditorForm
 *
 * Generic form for form editor
 *
 * @package nsfw\formeditor
 *
 */
class FormEditorForm extends AbstractForm{
  public function loadFromFile($file) {
    $path = getConfig()->projectRoot.'/data/forms';
    $loader = new XmlFormLoader(':'.$path.'/'.$file);
    $this->load($loader);
  }

}
