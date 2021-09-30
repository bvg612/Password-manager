<?php

namespace nsfw\template;


interface VarProcessor {
  /**
   * @param string $varName Variable name - unchanged, as it is in template, without {% and }.
   *      '{%q_varName}' translates to 'q_varName'
   *      The processor may change var name to strip the prefix/suffix that is specific to this processor
   *      If return value is false the processor MUST NOT modify $varName!
   * @param string $varValue - value as found in template.
   * @return boolean|string returns string value or false if not processed by this processor
   */
  function processExistingVar(&$varName, $varValue);

  /**
   * @param $varName
   * @param Template $tpl needed for some processors to get variable values. Processors that do not use it can allow
   * null value for easier testing.
   * @return bool|string returns string value or false if not processed by this processor.
   */
  function processMissingVar(&$varName, Template $tpl);

}
