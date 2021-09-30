<?php

namespace nsfw\template;


class HtmlEscapeProcessor implements VarProcessor {
  public function processExistingVar(&$varName, $varValue) {
    return false;
  }

  public function processMissingVar(&$varName, Template $tpl, $varValue = null) {
    $firstTwo = substr($varName, 0, 2);
    if($firstTwo == 'q:' || $firstTwo == 'q_') {
      $varName = substr($varName, 2);
      if(is_null($varValue)) {
        if($tpl instanceof CascadedTemplate) {
          $varValue = $tpl->getReplaceValue($varName);
        } else {
          $varValue = $tpl->getVar($varName);
        }
      }
      if(empty($varValue))
        return $varValue;
      return str_replace(array('   ', '  '), array('&nbsp; ', '&nbsp; &nbsp;'), nl2br(htmlSpecialChars($varValue, ENT_QUOTES)));
    }
    return false;
  }

}
