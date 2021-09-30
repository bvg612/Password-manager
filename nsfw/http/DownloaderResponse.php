<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 22-04-16
 * Time: 10:35 AM
 */

namespace nsfw\http;


use DOMDocument;
use Exception;
use tidy;

/**
 * Class DownloaderResponse
 * @package nsfw\http
 * @property string $contentType
 * @property string $url
 * @property string $headersStr
 * @property array $headers
 * @property int $httpCode
 * @property string $body
 *
 * @property string $error
 * @property int $errno
 */
class DownloaderResponse {

  private $fields = [
    'contentType' => '',
    'url' => '',
    'headersStr' => '',
    'headers' => [],
    'httpCode' => 0,
    'body' => '',

    'error' => '',
    'errno' => 0,
  ];

  /**
   * DownloaderResponse constructor.
   * @param array $properties
   */
  public function __construct($properties = []) {
    foreach($properties as $property=>$value) {
      if(isset($properties[$property]))
        $this->fields[$property] = $value;
    }
  }


  public function getSimpleXml(){
    libxml_use_internal_errors(true);
    $config = array(
      'clean' => 'yes',
      'output-html' => 'yes',
    );
    $contents = trim($this->body);
    if(empty($contents)){
      return false;
    }
    if(function_exists('tidy_parse_string')) {
      /** @var tidy $tidy */
      $tidy = tidy_parse_string($contents, $config, 'utf8');
      $tidy->cleanRepair();
    }else {
      $tidy = $contents;
    }
    $doc = new DOMDocument();
    $doc->strictErrorChecking = false;
    $doc->loadHTML($tidy);
    libxml_clear_errors();
    return simplexml_import_dom($doc);
  }

  public function __isset($name) {
    return array_key_exists($name, $this->fields);
  }

  public function __get($name) {
    if(!$this->__isset($name))
      throw new Exception('property (magic) $name does not exist');
    return $this->fields[$name];
  }

  public function __set($name, $value) {
    throw new Exception('Property '.$name.' is read only');
  }
}
