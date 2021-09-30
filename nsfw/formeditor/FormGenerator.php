<?php

namespace nsfw\formeditor;
use DOMDocument;
use nsfw\forms\AbstractForm;
use nsfw\forms\Form;
use nsfw\forms\FormField;
use nsfw\forms\WebForm;
use SimpleXMLElement;

/**
 * Class FormGenerator
 *
 * Creates php template and xml for the form
 *
 * @package nsfw\formeditor
 *
 */
class FormGenerator {
  /** @var AbstractForm */
  private $form;

  /** @var string */
  private $templateFile;

  /** @var bool */
  private $translate = false;

  /** @var string */
  private $formDir;

  private $template = '';

  /**
   * FormGenerator constructor.
   * @param string $form
   */
  public function __construct($form = '\nsfw\forms\WebForm') {
    if(is_string($form)) {
      if(!is_a($form, '\nsfw\forms\Form'))
        $this->form = new $form();
    } else if($form instanceof \nsfw\forms\Form){
      $this->form = $form;
    }
  }

  /**
   * @return boolean
   */
  public function isTranslate() {
    return $this->translate;
  }

  /**
   * @param boolean $translate
   */
  public function setTranslate($translate) {
    $this->translate = $translate;
  }

  /**
   * @return string
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * @param string $template
   */
  public function setTemplate($template) {
    $this->template = $template;
  }



  public function loadForm($formFile) {
    $this->form = new FormEditorForm();
    $this->form->loadFromFile($this->formDir.'/'.$formFile);
  }

  public function saveForm($formFile) {
    $xml = new SimpleXMLElement('<form></form>');
    $this->saveFormAttributes($xml);
    $this->saveFormFields($xml);
  }

  /**
   * @return WebForm|AbstractForm|Form
   */
  public function getForm() {
    return $this->form;
  }
  /**
   * @param SimpleXMLElement $xml
   */
  private function saveFormAttributes(SimpleXMLElement $xml) {
    $form = $this->form;
    $attributes = $form->getAttributes();
    $xml->addChild('action', $attributes['action']);
    unset($attributes['action']);
    $xml->addChild('template', $this->templateFile);
    $xml->addChild('translate', $this->translate?'true':'false');

    foreach($attributes as $attribute => $value) {
      /** @var SimpleXMLElement $eAttr */
      $eAttr = $xml->addChild('attribute', $value);
      $eAttr->addAttribute('name', $attribute);
    }

  }

  private function saveFormFields(SimpleXMLElement $xml) {
    $fields = $this->form->getFields();
    foreach($fields as $name=>$field) {
      /** @var FormField $field */
      $eField = $xml->addChild('field');
      $eField->addChild('type', $field->getType());
    }
  }

  private function formatSimpleXml(SimpleXMLElement $xml) {
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    return$dom->saveXML();
  }

  public function genXml() {
    $form = $this->form;
    $attributes = $form->getAttributes();
    $nl = "\r\n";
    $nl = "\n";
    $initXml = '<?xml version="1.0" encoding="UTF-8" ?>'.$nl.
    '<form xmlns="http://nsfw3.nicksoft.info/form.xsd">'.$nl.
    "</form>".$nl;

    $xml = new SimpleXMLElement($initXml);
    $xml->addChild('action', $attributes['action']);
    unset($attributes['action']);
    foreach($attributes as $name => $value) {
      if(!empty($value))
        $xml->addChild('attribute', $value)->addAttribute('name', $name);
    }
    $xml->addChild('translate', $this->translate?'true':'false');

    if(!empty($this->template))
      $xml->addChild('template', $this->template);
    var_dump($this->formatSimpleXml($xml));
  }

  public function dumpForm() {
    $form = $this->form;
    $attributes = $form->getAttributes();
    var_dump($attributes);
  }

}
