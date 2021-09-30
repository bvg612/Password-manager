<?php

namespace nsfw\forms;


use Exception;
use nsfw\errors\iErrorReporter;
use nsfw\errors\NullErrorReporter;
use nsfw\i18\Language;
use nsfw\template\CascadedTemplate;
use nsfw\template\DisplayObject;
use nsfw\template\Template;
use nsfw\validators\Validator;

abstract class AbstractForm implements Form, DisplayObject{
  protected $submitted = false;
  protected $fields = [];
  protected $formValid = false;
  /** @var CascadedTemplate */
  protected $tpl;
  protected $addSubmittedField = true;

  /** @var iErrorReporter */
  protected $errorReporter;

  protected $attributes = [
    'action' => '',
    'class' => '',
    'method' => 'post',
    'onsubmit' => null,
    'name' => null,
    'id' => null,
    'onreset' => null,
    'enctype' => null, //multipart/form-data
  ];

  /**
   * AbstractForm constructor.
   */
  public function __construct() {
    $this->errorReporter = NullErrorReporter::getInstance();
  }

  function setAttribute($attr, $value) {
    $attr = strToLower($attr);
    if(!array_key_exists($attr, $this->attributes)) {
      throw new Exception('Invalid form attribute "' . $attr . '"');
    }

    $this->attributes[$attr] = $value;
  }

  /**
   * @param string $attr
   * @return mixed|null
   * @throws Exception
   */
  function getAttribute($attr) {
    $attr = strToLower($attr);
    if(!array_key_exists($attr, $this->attributes))
      throw new Exception('Invalid form attribute "'.$attr.'"');
    if(!isset($this->attributes[$attr])) {
      throw new Exception('Attribute "' . $attr . '" was never set');
    }
    return $this->attributes[$attr];
  }

  public function getAttributes() {
    $attributes = $this->attributes;
    foreach($attributes as $key=>$value) {
      if(is_null($key))
        unset($attributes[$key]);
    }
    return $attributes;
  }


  /**
   * @return CascadedTemplate
   */
  public function getTemplate() {
    return $this->tpl;
  }

  /**
   * @param CascadedTemplate|Template $tpl
   */
  public function setTemplate(Template $tpl) {
    $this->tpl = $tpl;
  }

  function setTemplateFile($file) {
    $tpl = new CascadedTemplate();
    $tpl->loadFromFile($file);
    $this->tpl = $tpl;
  }


  /**
   * @return iErrorReporter
   */
  public function getErrorReporter() {
    return $this->errorReporter;
  }

  public function setErrorReporter(iErrorReporter $errorReporter) {
    $this->errorReporter = $errorReporter;
  }

  /**
   * @param FormField $ff
   * @return FormField
   */
  public function addField(FormField $ff) {
    $this->fields[$ff->getName()] = $ff;
    if($ff->getErrorReporter() === NullErrorReporter::getInstance())
      $ff->setErrorReporter($this->errorReporter);
    return $ff;
  }

  /**
   * Add a built-in field.
   *
   * @param string $name Field name
   * @param string $type Built in form field type or class name.
   * @param string $default Default value for the field
   * @param array|string|Validator $validators A validator (string name or object of Validator) or array of validators.
   *
   * @return FormField
   *
   * @throws Exception
   */
  public function addNewField($name, $type, $default = '', $validators = null) {

    $ffClass = AbstractFormField::getClass($type);

    if(empty($ffClass))
      throw new Exception('Invalid field type '.$type);

    $implements = class_implements($ffClass);
    if(!in_array('nsfw\forms\FormField', $implements))
      throw new Exception('Invalid field type '.$type);

    /** @var FormField $field */
    $field = new $ffClass($name);
    $field->setValue($default);
    $this->addField($field);

    if(!empty($validators)){
      if(is_string($validators) || ($validators instanceof Validator)){
        $field->addValidator($validators);
      }else if(is_array($validators)){
        foreach($validators as $validator){
          $field->addValidator($validator);
        }
      }
    }
    return $field;
  }

  /**
   * @param string $name
   * @return FormField
   * @throws Exception
   */
  public function getField($name) {
    if(empty($this->fields[$name]))
      throw new Exception('Field "'.$name.'" does not exist in this form.');
    return $this->fields[$name];
  }

  public function getFields() {
    return $this->fields;
  }

  /**
   * Returns form data
   *
   * @param array $translate form field name is the key, translated (database) field name is the value
   * @return array
   */
  public function getData($translate = []) {
    $data = [];
    foreach($this->fields as $field) {
      /** @var FormField $field */
      $dataIndex = $field->getName();
      if(array_key_exists($dataIndex, $translate))
        $dataIndex = $translate[$dataIndex];
      $data[$dataIndex] = $field->getValue();
    }
    return $data;
  }

  /**
   * @param array $data
   * @param array $translate
   * @return array
   */
  public function setData(array $data, $translate = []) {
    foreach($this->fields as $field) {
      /** @var FormField $field */
      $dataIndex = $field->getName();
      if(array_key_exists($dataIndex, $translate))
        $dataIndex = $translate[$dataIndex];
      if(array_key_exists($dataIndex, $data))
        $field->setValue($data[$dataIndex]);
    }
    return $data;
  }

  function isSubmitted() {
    if($this->submitted) // once we know it's submitted we don't have to check anymore
      return $this->submitted;
    $this->submitted = (getParam('submitted', false) !== false);
    return $this->submitted;
  }

  /**
   * @return boolean True if form is valid, false if it's not valid or not validated
   */
  public function isValid() {
    return $this->formValid;
  }



  /**
   * Acquires form values from post and returns true if form is submitted. If $validate is true the form is also
   * validated.
   *
   * @param bool $validate
   * @return bool
   */
  function processPost($validate = true) {
    $this->formValid = false;
    if(!$this->isSubmitted())
      return false;

    foreach($this->fields as $name=>$field){
      /** @var FormField $field */
      $field->getFromPost();
    }
    if(!$validate)
      return true;

    return $this->validate();
  }

  public function load(FormLoader $loader) {
    $loader->loadForm($this);
  }

  public function loadFromXmlFile($filename, Language $translator = null) {
    $loader = new XmlFormLoader(':'.$filename);
    $this->load($loader);
  }

  protected function buildAttributes() {
    $result = '';
    foreach($this->attributes as $attribute=>$value) {
      if(empty($value))
        continue;
      $result .= ' '.$attribute .'="'.htmlspecialchars($value).'"';
    }
    return $result;
  }

  /**
   * @return string
   */
  protected function getFormTag() {
    return '<form'.$this->buildAttributes().'>';
  }

  function getHtml() {
    $tpl = $this->tpl;
    $tpl->setVar('f_closeForm', '</form>');
    $tpl->setVar('f_formTag', $this->getFormTag());
    $this->setTemplateFields();
    if($tpl->getVar('submitTitle') == '')
      $tpl->setVar('submitTitle', 'Save');
    $this->setHiddenFields($tpl);
    return $this->tpl->getParsed();
  }

  function __toString() {
    return $this->getHtml();
  }


  private function setTemplateFields() {
    $row = $this->tpl->getBlock('field');
    $row->clearRows();
    foreach($this->fields as $name => $field) {
      /** @var FormField $field*/
      if($field instanceof HiddenField)
        continue;
      $row = $row->appendRow([
        'label' => $field->getLabelHtml(),
        'field' => $field->getHtml(),
      ]);

    }
  }

  public function dumpFields() {
    foreach($this->fields as $name => $field) {
      /** @var FormField $field */
      echo $name . "<br />";
    }
  }

  protected function setHiddenFields(Template $tpl) {
    $html = '';
    foreach($this->fields as $name => $field) {
      /** @var FormField $field*/
      if(!($field instanceof HiddenField))
        continue;
      $html .= $field->getHtml();
    }
    $html .= '<input type="hidden" name="submitted" value="1" />';
    $tpl->setVar('f_hiddenFields', $html);
  }

  protected function validate() {
    $this->formValid = true;
    foreach($this->fields as $name => $field) {
      /** @var FormField $field */
      if(!$field->validate())
        $this->formValid = false;
    }
    return $this->formValid;
  }

  public function __isset($name) {
    return array_key_exists($name, $this->fields);
  }

  public function __get($name) {
    if(!$this->__isset($name))
      return null;
    return $this->fields[$name];
  }

  public function __set($name, $value) {
    if (!isset($this->fields[$name]))
      throw new \Exception('FormField '.$name.' not found');
    $this->fields[$name]->value = $value;
  }


}
