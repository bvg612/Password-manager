<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 5/22/2018
 * Time: 11:19 AM
 */

namespace nsfw\template;


trait TemplateTraits {

  function setVars(array $vars) {
    $this->vars = $vars;
  }

  public function addVars(array $vars) {
    foreach($vars as $varName=>$varValue) {
      $this->setVar($varName, $varValue);
    }
  }
}
