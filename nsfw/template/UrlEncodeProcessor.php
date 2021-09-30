<?php
/**
 * User: npelov
 * Date: 10-07-17
 * Time: 3:25 PM
 */

namespace nsfw\template;


class UrlEncodeProcessor implements VarProcessor {
  public function processExistingVar(&$varName, $varValue) {
    return false;
  }

  public function processMissingVar(&$varName, Template $tpl) {
    $firstTwo = substr($varName, 0, 2);
    if($firstTwo == 'u:' || $firstTwo == 'u_') {
      $varName = substr($varName, 2);
      if($tpl instanceof CascadedTemplate) {
        $varValue = $tpl->getReplaceValue($varName);
      }else {
        $varValue = $tpl->getVar($varName);
      }
      if(empty($varValue))
        return $varValue;
      return urlencode($varValue);
    }
    return false;
  }



}
