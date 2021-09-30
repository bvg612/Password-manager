<?php

namespace nsfw\forms;


use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use nsfw\Config;
use nsfw\i18\DbLanguage;
use nsfw\i18\Language;
use nsfw\template\CascadedTemplate;
use nsfw\validators\SimpleValidator;
use nsfw\validators\ValidatorFileUpload;
use nsfw\validators\ValidatorLength;
use nsfw\validators\ValidatorRegex;

/**
 * Class XmlFormLoader
 * @package nsfw\forms
 *
 *
 * @property Language $translator
 */
class XmlFormLoader implements FormLoader{

  /** @var DOMDocument */
  private $doc;
  /** @var Language */
  protected static $defaultTranslator;
  protected $translateForm = false;

  /**
   * XmlFormLoader constructor.
   * @param string $xmlString
   * @throws Exception
   */
  public function __construct($xmlString) {
    $doc = $this->doc = new DOMDocument();
    if(substr($xmlString, 0, 1) == ':') {
      $xmlFile = substr($xmlString, 1);
      $result = $doc->load($xmlFile);
    } else {
      $result = $doc->loadXML($xmlString);
    }
    if(!$result) {
      $this->libXmlError();
    }
  }

  /**
   * @param Language $translator
   */
  public static function setDefaultTranslator(Language $translator) {
    static::$defaultTranslator = $translator;
  }

  public function t($var, $lang = null) {
    return $this->translator->translate($var, $lang);
  }

  public function __get($name) {
    if($name == 'translator') {
      if(static::$defaultTranslator == null) {
        static::$defaultTranslator = getConfig('translator');
        //static::$defaultTranslator = DbLanguage::getInstance();
      }
      return static::$defaultTranslator;
    }
    trigger_error('Unknown property(magic) "'.$name.'"', E_USER_ERROR);
    return null;
  }

  private function libXmlError() {
    $errors = libxml_get_errors();
    $firstError = reset($errors);
    if(empty($firstError)) {
      $msg = 'Unknown libxml error. Make sure libxml_use_internal_errors() set to true.';
    }else {
      /** @var \LibXMLError $firstError */
      $msg = $firstError->message. '. line '.$firstError->line;
    }
    //var_dump($firstError);
    throw new Exception($msg);
  }

  function loadForm(Form $form) {
    $doc = $this->doc;
    $eForm = $this->xmlGetElementByTag($doc, 'form');
    $action = $this->xmlGetElementByTag($eForm, 'action');
    $fclass = $this->xmlGetElementByTag($eForm, 'fclass', 0);

    $this->translateForm = $this->getBoolFromElement($this->xmlGetElementByTag($eForm, 'translate'), false);

    // @ToDo: Make sure variables are accepted in action (or any attribute
    if($action) {
      $form->setAttribute('action', $action->textContent); //nsTemplate::parseGlobalVarsString($action->textContent)
    }


    if($fclass) {
      $fclass = $action->textContent; //nsTemplate::parseGlobalVarsString($action->textContent)
    }else {
      $fclass ='';
    }

    try {
      $templateFile = $this->xmlGetElementByTag($eForm, 'template');
      $templateFile = trim($templateFile->textContent);
    }catch (Exception $ignored){
    }
    if (!empty($templateFile))
      $form->setTemplateFile($templateFile);

    $formAttributes = $doc->childNodes->item(0)->childNodes;
    foreach($formAttributes as $formAttr){
      if(!($formAttr instanceof DOMElement))
        continue;
      if($formAttr->tagName != 'attribute')
        continue;
      /** @var DOMElement $formAttr */
      $form->setAttribute($formAttr->getAttribute('name'), $formAttr->textContent);
    }


    $fields = $doc->getElementsByTagName('field');
    $count = $fields->length;
    foreach($fields as $xmlField){
      /** @var FormField $field */
      if(!($xmlField instanceof DOMElement))
        continue;
      $ff = $this->loadFormField($form, $xmlField, $fclass);
    }

  }

  private function loadFormField(Form $form, DOMElement $xmlField, $fclass = '') {
    $fieldName = $xmlField->getAttribute('name');
    /** @var FormField $field */
    $field = $form->addNewField(
      $fieldName,
      $this->xmlGetElementByTag($xmlField, 'type')->textContent
    );
    //echo $xmlField->getAttribute('name').'<br />';

    $this->setLabelFromXmlElement($field, $xmlField);
    try {
      $xmlValue = $this->xmlGetElementByTag($xmlField, 'label');
      $label = $xmlValue->textContent;
      if($this->translateForm)
        $label = $this->t($label);
      $field->setLabel($label);
    }catch (Exception $ignored) { }

    $validators = $xmlField->getElementsByTagName('validator');
    foreach($validators as $validator){
      $this->addXmlValidatorToFiled($field, $validator);
    }

    $attributes = $xmlField->getElementsByTagName('attribute');
    foreach($attributes as $attribute){
      if(!($attribute instanceof DOMElement))
        continue;
      $field->setAttribute($attribute->getAttribute('name'), $attribute->textContent);
      //echo "attribute (".$attribute->getAttribute('name').') -> '.$attribute->textContent.'<br />';
    }
    if(!empty($fclass)) {
      $field->addClass($fclass);
    }

    try {
      $this->xmlGetElementByTag($xmlField, 'required');
      $field->setRequired(true);
      //$field->insertValidator('required'); //addFirst
    }catch (Exception $ignored) {
    }

    try {
      $value = $this->xmlGetElementByTag($xmlField, 'value');
      $field->setValue($value->textContent);
      //$field->setValue(CascadedTemplate::parseGlobalVarsString($value->textContent));
    }catch (Exception $ignored) {
    }
    return $field;
  }

  /**
   * @param DOMElement|DOMDocument $parent
   * @param string $tagName
   * @param int $n
   *
   * @return DOMElement
   *
   * @throws Exception
   */
  private function xmlGetElementByTag($parent, $tagName, $n = 1) {
    $elements = $parent->getElementsByTagName($tagName);
    if($elements->length < $n)
      throw new Exception('Tag '.$tagName. '['.$n.'] does not exist.');

    $result = $elements->item($n-1);
    /** @var DOMElement $result*/
    return $result;
  }

  protected function getBoolFromString($string, $default = false) {
    if(empty($string))
      return $default;
    return strtolower($string) == 'true' || strtolower($string) == 'yes';
  }

  /**
   * @param DOMElement $xmlBool
   * @param bool $default
   * @return bool
   */
  protected function getBoolFromElement(DOMElement $xmlBool, $default = false) {
    if(empty($xmlBool))
      return $default;
    return $this->getBoolFromString($xmlBool->textContent, $default);
  }

  /**
   * @param DOMDocument $doc
   * @param DOMNode $node
   * @param string $xpathQueryStr
   *
   * @return DOMNodeList
   */
  private function xpathQuery(DOMDocument $doc, DOMNode $node, $xpathQueryStr){
    static $xpath = null;
    static $xpathDoc = null;
    if($doc !== $xpathDoc){
      $xpathDoc = $doc;
      $xpath = new DOMXpath($doc);
    }
    return $xpath->query($xpathQueryStr, $node);
  }

  public function setLabelFromXmlElement(FormField $field, DOMElement $xmlField){
    /** @var DOMElement $xmlLabel */
    try {
      $xmlLabel = $this->xmlGetElementByTag($xmlField, 'label');
    }catch (Exception $e) {
      return;
    }

    $translate = $this->getBoolFromString($xmlLabel->getAttribute('translate'), $this->translateForm);
    $label = $xmlLabel->textContent;
    if($translate){
      $field->setLabel($this->t($label));
    }else{
      $field->setLabel($label);
    }
  }

  /**
   * @param FormField $field
   * @param DOMElement $validator
   * @throws Exception
   */
  private function addXmlValidatorToFiled($field, $validator) {
    $validatorType = $validator->getAttribute('type');
    if(SimpleValidator::validatorExists($validatorType)){
      $field->addValidator($validatorType);
      return;
    }
    switch($validatorType){
      case 'regex':
        $pattern = $this->xmlGetElementByTag($validator, 'pattern')->textContent;
        $field->addValidator(new ValidatorRegex($pattern));
        break;
      case 'upload':
        $extensions = $this->xmlGetElementByTag($validator, 'ext')->textContent;
        $v = new ValidatorFileUpload();
        $v->setAllowedExtensions($extensions);
        $v->setErrorReporter($field->getErrorReporter());
        $field->addValidator($v);
        break;

      case 'length':
        $min = -1;
        $max = -1;
        $eMin = $this->xmlGetElementByTag($validator, 'min');
        $eMax = $this->xmlGetElementByTag($validator, 'max');
        if($eMin)
          $min = $eMin->textContent;
        if($eMax)
          $max = $eMax->textContent;
        $v = new ValidatorLength($min, $max);
        $v->setErrorReporter($field->getErrorReporter());
        $field->addValidator($v);
        break;
      // simple validators
      case 'email':
      case 'integer':
      case 'positiveOrZero':
      case 'positive':
      case 'number':
        $field->addValidator($validatorType);
        break;
      default:
        throw new Exception('Invalid validator '.$validatorType.' passed to xml file');
    }
  }


}
