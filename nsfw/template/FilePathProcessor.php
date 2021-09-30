<?php

namespace nsfw\template;


use Exception;

class FilePathProcessor implements VarProcessor {
  /** @var TemplateConfig */
  protected $tplConfig;

  public function __construct(TemplateConfig $tplConfig) {
    $this->tplConfig = $tplConfig;
  }

  public function __destruct() {
    $this->tplConfig = null;
  }

  /**
   * Converts relative template path to an absolute path to the right template directory. First the subtemplate, then
   * the main template directory is searched for the file.
   *
   * @param $relativePath
   * @return array Numeric array of found file's absolute path and the template directory it was found in
   * @throws Exception
   */
  public function findFileInTemplates($relativePath) {
    $config = $this->tplConfig;

    $mainPath = $config->mainDir . '/' . $relativePath;
    if(!empty($config->subtemplateDir)) {
      $foundFile = $config->subtemplateDir . '/' . $relativePath;
      $dir = $config->subtemplateDir;
    } else {
      $foundFile = $mainPath;
      $dir = $config->mainDir;
    }

    // not found in main dir
    if(!is_file($foundFile) && $foundFile != $mainPath) {
      // try sub template dir
      $foundFile = $mainPath;
      $dir = $config->mainDir;
    }

    if(!is_file($foundFile)) {
      if(TemplateConfig::$debug) {
        var_dump($config->mainDir);
        var_dump($config->subtemplateDir);
      }
      throw new Exception("File not found in template dirs: " . $relativePath);
    }

    if(!is_file($foundFile))
      throw new Exception('Not a file: ' . $foundFile);


    return [$foundFile, $dir];
  }

  /**
   * Converts relative template path to an absolute path to the right template directory. First the subtemplate, then
   * the main template directory is searched for the file.
   * @param string $relativePath Path, relative to the template root
   * @return string Absolute file path
   * @throws Exception
   */
  public function getFilePath($relativePath) {
    list($foundFile, ) = $this->findFileInTemplates($relativePath);

    return $foundFile;
  }

  protected function isAParsedResource($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, $this->tplConfig->parsedFileTypes);
  }

  /**
   * Calculate relative path from absolute paths $child and $parent. $child must be subdirectory of $parent
   *
   * @param string $parent Parent directory
   * @param string $child Child directory
   * @return string $child path, relative to $parent
   * @throws Exception
   */
  public static function getRelativePath($parent, $child) {
    $parent = str_replace('\\', '/', $parent);
    $child = str_replace('\\', '/', $child);
    $pos = strpos($child, $parent);

    if($pos !== 0)
      throw new Exception('Template config vars mainDir and tplConfig must be absolute directories with common partent dir.');

    $len = strlen($parent);
    if($len > strlen($child))
      throw new Exception('mainDir must be in tplRoot');

    return ltrim(substr($child, $len), '\\/');
  }

  protected function getMainTemlpateRelative() {
    return self::getRelativePath($this->tplConfig->tplRootDir, $this->tplConfig->mainDir);
  }

  protected function getSubTemplateRelative() {
    return self::getRelativePath($this->tplConfig->tplRootDir, $this->tplConfig->subtemplateDir);
  }

  /**
   * @param string $absolutePathPrefix absolute path, not including the domain
   * @param string $relativePath
   * @return string
   */
  public static function completeWebPath($absolutePathPrefix, $relativePath) {
    if($relativePath{0} == '/') {
      $relativePath= substr($relativePath, 1);
    }
    if($absolutePathPrefix == '/')
      return $absolutePathPrefix . $relativePath;
    else if(preg_match('@^https?://[a-z0-9\\.-]+(:[0-9]+)?/$@', $absolutePathPrefix)) {
      return $absolutePathPrefix . $relativePath;
    }
    return $absolutePathPrefix . '/' . $relativePath;
  }

  /*
  public function getRelativePath() {

  }*/

  public function getAbsoluteWebPath($relativePath) {
    return self::completeWebPath($this->tplConfig->webPath, $relativePath);
  }

  public function getPathRelativeToTplDir($tplPath) {
    list($absoluteFilePath, $tplDir) = $this->findFileInTemplates($tplPath);
    $tplRelative = self::getRelativePath($tplDir, $absoluteFilePath);
    return $tplRelative;
  }

  public function getPathRelativeToTplRoot($tplPath) {
    $config = $this->tplConfig;
    $absoluteFilePath = $this->getFilePath($tplPath);
    $tplRelative = self::getRelativePath($config->tplRootDir, $absoluteFilePath);
    return $tplRelative;
  }

  /**
   * Translates template path to absolute web path.
   *
   * If a loader script is used it's converted to /resource-script.php?r=<tpl-root-dir>/relative/tpl/path/file.css
   *   where <tpl-root-dir> is the top-most directory that's common for all templates
   *
   * @param string $relativePath Path, relative to the web root.
   * @return string Absolute web path.
   */
  public function getTplWebPath($relativePath) {
    $isParsed = $this->isAParsedResource($relativePath);
    $config = $this->tplConfig;

    $tplRootdir = basename($config->tplRootDir);


    if(!empty($config->tplWebPath)) {
      // visible on web
      $tplPath = $this->getAbsoluteWebPath($config->tplWebPath . '/' . $this->getPathRelativeToTplRoot($relativePath));
      //var_dump('FPprocessor: '.$this->getPathRelativeToTplRoot($relativePath));

      if($isParsed) {
        if($config->useModRewrite) {
          return $tplPath;
        } else {
          return $this->getAbsoluteWebPath($config->loaderScript . '?r=' . $this->getPathRelativeToTplDir($relativePath));
        }
      }
      return $tplPath;
    } else {
      // not visible on web
      return $this->getAbsoluteWebPath($config->loaderScript . '?r=' . $this->getPathRelativeToTplDir($relativePath));

    }

  }

  public function getWebPath($path) {
    //$config = $this->tplConfig;

    if($path{0} == '/') { // relative to web path
      $webPath = $this->getAbsoluteWebPath($path);
    } else {
      // relative to current web path
      throw new Exception('Relative to current path is not implemented yet: '.$path);
    }
    return $webPath;
  }

  /**
   * @codeCoverageIgnore
   */
  public function processExistingVar(&$varName, $varValue) {
    return false;
  }

  public function processMissingVar(&$varName, Template $tpl = null) {
    if(!preg_match('@(file|web|tpl):(.*)@', $varName, $m))
      return false;

    $function = $m[1];
    $relativePath = $m[2];

    if($function == 'file')
      return $this->getFilePath($relativePath);

    if($function == 'tpl') {
      //var_dump('FPprocessor1: '.$this->getPathRelativeToTplRoot($relativePath));
      //var_dump('FPprocessor2: '.$this->getTplWebPath($relativePath));

      return $this->getTplWebPath($relativePath);
    }

    if($function == 'web')
      return $this->getWebPath($relativePath);

    throw new Exception('This place should be unreachable');
  }

}
