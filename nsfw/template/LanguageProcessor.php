<?php

namespace nsfw\template;


use nsfw\i18\DbLanguage;
use nsfw\i18\Language;

class LanguageProcessor implements VarProcessor{
  /** @var HtmlEscapeProcessor  */
  private static $escapeProcessor;
  /** @var DbLanguage|Language */
  protected $language;

  public function __construct(Language $language = null) {
    if(empty($language))
      $language = DbLanguage::getInstance();
    $this->language = $language;
    if(empty(self::$escapeProcessor))
      self::$escapeProcessor = new HtmlEscapeProcessor();
  }

  private function getLangVar($varName, $function) {
    /** @ToDo: Implement translation */
    $language = $this->language;
    switch($function) {
      case 'f':
        return $language->translateUFirst($varName);
      case 'w':
        return $language->translateUWords($varName);
      case 'u':
        return $language->translateUpper($varName);
      case 'l':
        return $language->translateLower($varName);
    }
    return $language->translate($varName);;
  }

  public function processExistingVar(&$varName, $varValue) {
    return false;
  }

  /**
   * @param string $varName varName prefixed by l_. Optionally prefix can have an additional "q" in front or  one of
   *  f, w, u or l after the letter "l".
   * "q" means to quote/escape HTML
   *  f, w, u and l are modifiers as follows:
   * f - upper case first letter of the text
   * w - upper case first letter of every word
   * u - upper case the whole string
   * l - lower case the whole string
   * @param Template $tpl
   * @return bool|mixed|DisplayObject|null|string
   */
  public function processMissingVar(&$varName, Template $tpl) {
    if(!preg_match('/^(q?)l([fwul]?)_(.*)$/', $varName, $m))
      return false;

    $varName = $m[3];
    $value = $this->getLangVar($varName, $m[2]);
    if($m[1] == 'q') {
      //$varName = $varName; // make sure quote processor wants it ... ToDo: Chaining processors doesn't work!!!
      return self::$escapeProcessor->processMissingVar($varName, MockTemplate::getInstance($varName, $value));
    }
    return $value;
  }


}
