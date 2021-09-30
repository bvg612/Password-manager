<?php

namespace nsfw\template;


use Exception;
use nsfw\cache\Cache;
use nsfw\cache\NullCache;

abstract class AbstractTemplate implements Template {


  /** @var string  */
  protected $template = '';

  /** @var  TemplateConfig */
  protected $config;

  /** @var array */
  protected static $defaultProcessors = [];
  /** @var array custom processors*/
  protected $processors = [];
  /** @var array build in processors*/
  protected $preProcessors = [];
  /** @var  Cache */
  protected $cache;

  /** @var  FilePathProcessor */
  protected $filePathProcessor;


  /** @var  TemplateConfig */
  protected static $defaultConfig;



  /**
   * @param TemplateConfig $config
   */
  public static function setDefaultConfig(TemplateConfig $config) {
    static::$defaultConfig = $config;
  }

  /**
   * @return TemplateConfig
   */
  public static function getDefaultConfig() {
    return static::$defaultConfig;
  }

  /**
   * @param array $settings
   */
  public static function loadDefaultConfig(array $settings) {
    $useSetterFor = [
      'tplpath' => true,
      'loaderscript' => true,
    ];
    $c = static::getDefaultConfig();
    foreach($settings as $name=>$value){
      if(property_exists($c, $name)) {
        if(empty($useSetterFor[strtolower($name)])) {
          $c->$name = $value;
        } else {
          $setter = 'set' . $name;
          $c->$setter($value);
        }
      }
    }
  }

  /**
   * @param $filename
   * @return static
   */
  public static function createFromFile($filename) {
    $tpl = new static();
    $tpl->loadFromFile($filename);
    return $tpl;
  }


  /**
   * AbstractTemplate constructor.
   * @param object|string $template
   * @param Cache|null $cache
   */
  public function __construct($template = '', Cache $cache = null) {
    if(empty($cache))
      $cache = new NullCache();
    $this->cache = $cache;
    if(!empty($template))
      $this->setTemplate($template);
    if($template instanceof AbstractTemplate) {
      $this->processors = &$template->processors;
    }else {
      $this->processors = self::$defaultProcessors;
    }
  }

  /**
   * @return Cache
   */
  public function getCache() {
    return $this->cache;
  }


  public static function addDefaultProcessor(VarProcessor $processor) {
    array_push(self::$defaultProcessors, $processor);
  }

  public static function insertDefaultProcessor(VarProcessor $processor) {
    array_unshift(self::$defaultProcessors, $processor);
  }

  public static function getDefaultProcessors() {
    return self::$defaultProcessors;
  }

  /**
   * Call this to create and add default processors for Html
   */
  public static function initDefaultHtmlProcessors($language = false){
    self::$defaultProcessors = [];
    if($language)
      self::addDefaultProcessor(new LanguageProcessor());
    self::addDefaultProcessor(new UrlEncodeProcessor());
    self::addDefaultProcessor(new HtmlEscapeProcessor());
  }

  public function addProcessor(VarProcessor $processor) {
    //$this->processors[get_class($processor)] = $processor;
    $this->processors[] = $processor;
  }

  public function getProcessors() {
    return $this->processors;
  }

  public function insertProcessor(VarProcessor $processor) {
    //$this->processors = [get_class($processor) => $processor] + $this->processors;
    array_unshift($this->processors, $processor);
  }

  abstract function getParsed();

  function getHtml() {
    return $this->getParsed();
  }

  function __toString() {
    return $this->getHtml();
  }

  public function display() {
    echo $this->getParsed();
  }

  protected function runProcessorsOnExisting(&$varName, $varValue) {
    $returnValue = $varValue;
    foreach($this->processors as $processor) {
      /** @var VarProcessor $processor */

      $result = $processor->processExistingVar($varName, $returnValue);

      if($result === false)
        continue;

      $returnValue = $result;
    }
    return $returnValue;
  }

  protected function runProcessorListOnMissing($processorList, &$varName, Template $tpl) {
    foreach($processorList as $processor) {
      /** @var VarProcessor $processor */
      $varValue = $processor->processMissingVar($varName, $tpl);

      // not missing anymore?
      if($varValue !== false) {
        //we have a value
        return $varValue;
      }
    }

    return false;
  }

  protected function runProcessorsOnMissing(&$varName, Template $tpl) {
    $varValue = $this->runProcessorListOnMissing($this->preProcessors, $varName, $tpl);
    if($varValue !== false) {
      return $varValue;
    }
    $varValue = $this->runProcessorListOnMissing($this->processors, $varName, $tpl);
    return $varValue;
  }

  function __get($name) {
    if($this->hasVar($name))
      return $this->getVar($name);

    if($this->hasBlock($name))
      return $this->getBlock($name);

    throw new Exception('Magic property "'.$name.'" not found. ');
  }

  function __isset($name) {
    if($this->hasVar($name))
      return true;

    if($this->hasBlock($name))
      return true;

    return false;
  }


  function __set($name, $value) {

    // @ToDo:  Should I allow setting of block value?
    if($this->hasBlock($name)) {
      throw new Exception('Can not set value of block "'.$name.'"');
    }

    $this->setVar($name, $value);

  }


}
