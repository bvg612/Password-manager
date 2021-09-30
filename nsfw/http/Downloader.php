<?php

namespace nsfw\http;


class Downloader {
  const METHOD_PUT = 1;
  const METHOD_GET = 2;
  const METHOD_POST = 3;
  const METHOD_HEAD = 4;

  static $methods = [self::METHOD_PUT =>'PUT', self::METHOD_GET => 'GET', self::METHOD_POST => 'POST', self::METHOD_HEAD => 'HEAD'];

  protected $method = self::METHOD_GET;

  protected $agent = 'Mozilla/5.0 (compatible; Crolerbot/0.1; +http://www.croler.net/bot.html)';

  protected $ch;

  protected $options = [];

  protected $headers;

  protected static $optionsDefault = array(
    CURLOPT_HEADER => true,
    CURLOPT_HTTP200ALIASES => array(200, 201),
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CRLF => true,
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_RETURNTRANSFER => true,
  );

  /**
   * Downloader constructor.
   */
  public function __construct() {
    $this->ch = curl_init();
    $this->resetOptions();
  }

  public function resetOptions(){
    curl_setopt_array($this->ch, self::$optionsDefault);
    $this->options[CURLOPT_USERAGENT] = $this->agent;
  }

  /**
   * @param int $method
   */
  public function setMethod($method){
    assert(array_key_exists($method, self::$methods));
    $this->method = strToUpper($method);
    switch($this->method){
      case self::METHOD_GET:
        $this->options[CURLOPT_NOBODY] =  false;
        break;
      case self::METHOD_HEAD:
        $this->options[CURLOPT_NOBODY] =  true;
        break;
      case self::METHOD_POST:
        $this->options[CURLOPT_NOBODY] =  true;
        $this->options[CURLOPT_POST] =  true  ;
        break;
      case self::METHOD_PUT:
        $this->options[CURLOPT_NOBODY] =  true;
        $this->options[CURLOPT_PUT] =  true  ;
        break;
    }
  }

  /**
   * @return int
   */
  public function getMethod() {
    return $this->method;
  }

  private function parseHeaders(array &$responseFields) {
    $headers = explode("\r\n", $responseFields['headersStr']);
    $responseFields['httpResult'] = array_shift($headers);
    foreach($headers as $h){
      $f = explode(':', $h);
      $k = strToUpper(trim($f[0]));
      $v = null;
      if(isset($f[1]))
        $v = trim($f[1]);

      if(!isset($this->headers[$k])){
        $responseFields['headers'][$k] = array();
      }

      $responseFields['headers'][$k][] = $v;
    }
  }


  /**
   * @param string $url
   * @param int $method
   * @return bool|DownloaderResponse
   */
  public function download($url, $method = null) {
    curl_setopt_array($this->ch, $this->options);

    if(empty($method)) {
      $method = $this->method;
    }

    curl_setopt($this->ch, CURLOPT_URL, $url);


    $contents = curl_exec($this->ch);
    $responseFields = [];

    $curlInfo = curl_getinfo($this->ch);

    if($contents === false) {
      $responseFields['error'] = curl_error($this->ch);
      $responseFields['errno'] = curl_errno($this->ch);
    }

    if(!$curlInfo)
      return false;

    // Then, after your curl_exec call:
    $headerSize = $curlInfo['header_size'];
    $requestSize = $curlInfo['request_size'];
    $headers = substr($contents, 0, $headerSize);
    if($headerSize == strlen($contents)) {
      $responseFields['body'] = '';
    }else {
      $responseFields['body'] = substr($contents, $headerSize);
    }
    $responseFields['totalSize'] = strlen($contents);
    $responseFields['headerSize'] = $headerSize;
    $responseFields['headersStr'] = $headers;

    $responseFields['url'] = $curlInfo['url'];
    $responseFields['contentType'] = $curlInfo['content_type'];
    $responseFields['httpCode'] = $curlInfo['http_code'];

    $this->parseHeaders($responseFields);
    $response = new DownloaderResponse($responseFields);
    return $response;
  }

  public function downloadSimpleXml($url, $method){
    $response = $this->download($url, $method);
    if(!$response)
      return false;
    return $response->getSimpleXml();
  }

}
