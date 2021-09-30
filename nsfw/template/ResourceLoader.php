<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 16-04-16
 * Time: 8:38 PM
 */

namespace nsfw\template;
use nsfw\Mime;

/**
 * Class ResourceLoader
 *
 * Used to make resource loader script.
 *
 * Example:
 *
 * // init framework and configure template before creating the loader object
 * $loader = new ResourceLoader();
 * $loader->auto();
 *
 * Example mod_rewrite config:
 *
 *
 *
 * @package nsfw\template
 */
class ResourceLoader {
  /** @var CascadedTemplate  */
  protected $tpl;

  protected $textEncoding = 'utf-8';

  protected $config;

  /** @var string */
  protected $resource = '';

  /** @var  FilePathProcessor */
  protected $fpProcessor;

  /**
   * ResourceLoader constructor.
   * @param TemplateConfig $config
   */
  public function __construct(TemplateConfig $config = null) {

    if(empty($config))
      $config = CascadedTemplate::getDefaultConfig();
    else
      $config = clone $config; // we don't want to mess up the template config
    $this->config = $config;

    $this->tpl = new CascadedTemplate($config);


    $this->fpProcessor = new FilePathProcessor($config);
    if(isset($__GET['r']))
      $this->resource = $__GET['r'];
  }

  /**
   * @return string
   */
  public function getTextEncoding() {
    return $this->textEncoding;
  }
  /**
   * @param string $textEncoding
   */
  public function setTextEncoding($textEncoding) {
    $this->textEncoding = $textEncoding;
  }



  protected function notFound() {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit;
  }


  public function genHeaders($contents, $relativePath) {
    $headers = [];
    $config = $this->config;
    $fp = $this->fpProcessor;
    $fullPath = $fp->getFilePath($relativePath);
    $size = strlen($contents);
    $headers[] = 'Content-Length: '.$size;
    $mime = Mime::getInstance();
    $webPath = $fp->getTplWebPath($relativePath);
    //var_dump('webpath:', $relativePath, $webPath);
    $mimeType = $mime->getMime($webPath);
    $contentType = 'Content-Type: '.$mimeType;
    if(substr($mimeType, 0, 5) == 'text/')
      $contentType = 'Content-Type: '.$mimeType.'; ; charset='.$this->textEncoding;
    $headers[] = $contentType;
        /*
    // @ToDo: WTF?
    if($config->isAParsedResource($relativePath)) {
      return $webPath;
    }     */
    return $headers;
  }

  public function getResourceContents($relativePath) {

    $config = $this->config;
    $fp = $this->fpProcessor;
    $tpl = $this->tpl;
    if($config->isAParsedResource($relativePath)) {
      $tpl->loadFromFile($relativePath);
      return $tpl->getParsed();
    }else {
      httpRedirect($fp->getTplWebPath($relativePath), 301);
      return $config->loaderScript;
    }
  }

  public function loadResource($relativePath) {
    $contents =  $this->getResourceContents($relativePath);

    $headers = $this->genHeaders($contents, $relativePath);
    foreach($headers as $header) {
      header($header);
    }

    echo $contents;
    exit();
  }

  public function auto() {
    if(empty($this->resource)) {
      $this->notFound();
    }
    $this->loadResource($this->resource);
  }

}
