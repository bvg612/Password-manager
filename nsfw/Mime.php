<?php

namespace nsfw;


class Mime {
  protected static $instance;
  protected static $mimeTypes = [
    'txt' => 'text/plain',

    'html' => 'text/html',
    'htm' => 'text/html',
    'xhtml' => 'text/html',
    'shtml' => 'text/html',

    'css' => 'text/css',

    'js' => 'text/javascript',

    'xml' => 'text/xml',

    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'png' => 'image/png',
    'bmp' => 'image/bmp',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'svgz' => 'image/svg+xml',
  ];

  protected $override = [];
  protected $overridePattern = [];

  public function overrideMime($path, $mime) {
    if(strpos($path, '*') === false) {
      $this->override[$path] = $mime;
    }else {
      $this->overridePattern[$path] = $mime;
    }
  }

  protected function getOverrideMime($path) {
    if(empty($this->override[$path]))
      return false;
    return $this->override[$path];
  }

  protected function getOverrideWithPattern($path) {
    foreach($this->overridePattern as $pattern=>$mime) {
      if(strpos($pattern, '**') !== false && fnmatch($pattern, $path)) {
        return $mime;
      }

      if(fnmatch($pattern, $path, FNM_PATHNAME)) {
        return $mime;
      }
    }
    return false;
  }

  public function getMime($urlPath) {
    $override = $this->getOverrideMime($urlPath);
    if(!empty($override))
      return $override;

    $override = $this->getOverrideWithPattern($urlPath);
    if(!empty($override))
      return $override;

    $mime = self::getMimeForFile($urlPath);
    return $mime;
  }

  public static function getInstance() {
    if(empty(self::$instance)) {
      self::$instance = new Mime();
    }
    return self::$instance;
  }

  public static function getMimeForFile($file){

    $fileExt = strToLower(pathinfo($file, PATHINFO_EXTENSION));
    if(empty(self::$mimeTypes[$fileExt]))
      return false;
    return self::$mimeTypes[$fileExt];
  }

}
