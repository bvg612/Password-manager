<?php

namespace nsfw\codegenerator;
define('T_DIRECT', 0);

use Exception;

class ClassInspector {
  /** @var array */
  protected $classes = [];
  /** @var string */
  protected $namespace;
  /** @var int */
  protected $classState = 0;
  /** @var array */
  protected $uses = [];
  /** @var array */
  protected $interfaces = [];

  /** @var string */
  protected $currentUse = '';
  /** @var string */
  protected $currentUseClass = '';

  protected $lineNum;
  protected $token;
  protected $tokenName;
  protected $tokenType;


  /** @var int */
  protected $bracketLevel = 0;

  /** @var int */
  protected $namespaceState = 0;


  private $namespaceBuild = '';
  private $namespaceBracketLevel = 0;


  private function initVars() {
    $this->namespaceBuild = '';
    $this->namespaceBracketLevel = 0;
    $this->namespace = '\\';
    $this->uses = [];
    $this->bracketLevel = 0;
    $this->classState = 0;

  }

  protected function processNamespace() {
    $token = &$this->token;
    $tokenType = &$this->tokenType;
    $namespaceBuild = &$this->namespaceBuild;
    $namespaceBracketLevel = &$this->namespaceBracketLevel;
    $namespaceState = &$this->namespaceState;
    $bracketLevel = &$this->bracketLevel;

    switch($namespaceState) {
      case 0:
        if($tokenType == T_NAMESPACE) {
          $namespaceState = 1;
          $this->namespace = '\\';
        }
        break;
      case 1: // collecting namespace
        if($tokenType == T_STRING) {
          $namespaceBuild .= $token;
        }
        if($tokenType == T_NS_SEPARATOR) {
          $namespaceBuild .= $token;
        }
        if($tokenType == T_DIRECT && $token == ';') {
          $this->namespace = $namespaceBuild;
          $namespaceState = 0;
        }
        if($tokenType == T_DIRECT && $token == '{') {
          $namespaceBracketLevel = $bracketLevel;
          $this->namespace = $namespaceBuild;
          $namespaceState = 2; // inside namespace
        }

        break;
      case 2: // waiting for namespace end
        if($tokenType == T_DIRECT && $token == '}' && $bracketLevel == $namespaceBracketLevel) {
          $this->namespace = '\\';
          $namespaceState = 0; // not in namespace
          $namespaceBuild = '';
          $namespaceBracketLevel = -1;
        }
        break;
    }
  }
  /**
   * @param string $file
   * @return array
   * @throws Exception
   */
  public function collectFileClasses($file) {
    $classState = &$this->classState;
    $classes = &$this->classes;
    $interfaces = &$this->interfaces;
      /** @var PhpClass|null $currentClass */
    $bracketLevel = &$this->bracketLevel;
    $uses = &$this->uses;
    $namespace = &$this->namespace;

    $currentUse = &$this->currentUse;
    $currentUseClass = &$this->currentUseClass;

    $this->initVars();


    /**
     * @var PhpInterface $currentInterface
     * @var PhpClass $currentClass
     * @var string $currentImplements
     */
    $currentClass = null;
    $currentInterface = null;
    $currentImplements = '';

    $lineNum = &$this->lineNum;
    $token = &$this->token;
    $tokenType = &$this->tokenType;
    $tokenName = &$this->tokenName;



    $tokens = token_get_all(file_get_contents($file));

    static $skipTypes = [
      T_WHITESPACE => T_WHITESPACE,
      T_DOC_COMMENT => T_DOC_COMMENT,
      T_OPEN_TAG => T_OPEN_TAG,
    ];
    $finishClass = false;
    $finishInterface = false;
    foreach ($tokens as $tokenArr) {
      if(is_array($tokenArr) && array_key_exists($tokenArr[0], $skipTypes))
        continue;
      if(is_string($tokenArr)) {
        $token = $tokenArr;
        $tokenArr = [0, $token, 0];
        $tokenName = 'direct';
        $tokenType = T_DIRECT;
      } else if(is_array($tokenArr)){
        $lineNum = $tokenArr[2];
        $tokenType = $tokenArr[0];
        $tokenName = token_name($tokenType);
        $token = $tokenArr[1];
      }else{
        throw new Exception('Unsupported: '.gettype($tokenArr));
      }

      //echo "Line {$tokenArr[2]}: ", $tokenName, " ('{$token}'), NS: {$namespaceState}", PHP_EOL;

      if (is_array($tokenArr) || true) {

        if($tokenType == 'direct' && $token == '{') {
          ++$bracketLevel;
        }

        $this->processNamespace();


        if($this->namespaceState != 1)
        switch($classState) {
          case 0:
            $finishClass = false;
            if($tokenType == T_CLASS) {
              $currentClass = new PhpClass();
              $classState = 1;
              break;
            }

            if($tokenType == T_USE) {
              $classState = 5;
              $currentUse = '\\';
              break;
            }

            if($tokenType == T_INTERFACE) {
              $currentInterface = new PhpInterface();
              $classState = 6;
              break;
            }

            break;
          case 1:
            if($tokenType == T_STRING) {

              $currentClass->name = $token;
              $classState = 2;
            }
            break;

          case 2: // waiting for '{', extends or implements
            if($tokenType == T_EXTENDS) {
              $classState = 3;
            }

            if($tokenType == T_IMPLEMENTS) {
              $classState = 4;
            }

            if($tokenType == T_DIRECT && $token == '{') {
              $finishClass = true;
            }
            break;
          case 3: // get parent class
            if($tokenType == T_STRING) {
              $currentClass->appendToParent($token);
            }
            if($tokenType == T_NS_SEPARATOR) {
              $currentClass->appendToParent($token);
            }
            if($tokenType == T_IMPLEMENTS) {
              $currentImplements = '\\';
              $classState = 4;
            }
            if($tokenType == T_DIRECT && $token == '{') {
              $finishClass = true;
            }

            break;
          case 4:
            if($tokenType == T_STRING) {
              $currentImplements .= $token;
            }
            if($tokenType == T_NS_SEPARATOR) {
              $currentImplements .= '\\';
            }
            if( $tokenType == T_DIRECT && ($token = ',' || $token = '{')) {
              $currentClass->interfaces[] = $this->getFQN($currentImplements);
              $currentImplements = '\\';

              if($token == '{')
                $finishClass = true;
            }
          break;
          case 5: // collect use
            if($tokenType == T_STRING || $tokenType == T_NS_SEPARATOR) {
              if($tokenType == T_STRING) {
                $currentUseClass = $token; // simple class name
              }
              $currentUse .= $token;
            }

            if($tokenType == T_DIRECT && $token == ';') {
              $classState = 0;
              $uses[$currentUseClass] = $currentUse;
            }
            break;

          case 6: // collect interface
            if($tokenType == T_STRING || $tokenType == T_NS_SEPARATOR) {
              $currentInterface->name = $token;
            }

            if($tokenType == T_EXTENDS) {
              $classState = 7;
            }

            if($tokenType == T_DIRECT && $token == '{') {
              $finishInterface = true;
            }
            break;
          case 7: // get parent class
            if($tokenType == T_STRING) {
              $currentInterface->appendToParent($token);
            }
            if($tokenType == T_NS_SEPARATOR) {
              $currentClass->appendToParent($token);
            }
            if($tokenType == T_DIRECT && $token == '{') {
              $finishInterface = true;
            }

            break;

        }//switch
        if($finishClass) {
          $finishClass = false;
          $classState = 0;
          $this->fixFQN($currentClass->name);
          $this->fixFQN($currentClass->parent);
          if($currentImplements != '\\') {
            if(!empty($currentImplements))
              $currentClass->interfaces[] = $currentImplements;
          }

          $classes[$currentClass->name] = $currentClass;
          $currentClass = new PhpClass();
        }
        if($finishInterface) {
          $finishInterface = false;
          $this->fixFQN($currentInterface->name);
          foreach($currentInterface->parents as $parent) {
            $this->fixFQN($parent);
          }
          $interfaces[$currentInterface->name] = $currentInterface;
          $classState = 0;
        }
      }

      if($tokenType == T_DIRECT && $token == '}') {
        --$bracketLevel;
      }

    }// for
    //var_dump($this->interfaces);
    return $classes;
  }

  protected function collectClassesForDir($classDir, $subDir = '') {
    $dir = dir($classDir.$subDir);
    while (false !== ($entry = $dir->read())) {
      if($entry == '.' || $entry == '..')
        continue;
      $relativePath = $subDir.'/'.$entry;
      $fullPath = $classDir.$relativePath;
      if(is_dir($fullPath)) {

        $this->collectClassesForDir($classDir, $relativePath);
        continue;
      }
      echo $relativePath."\n";
      $this->collectFileClasses($fullPath);
    }
    $dir->close();
  }

  public function collectClasses(array $classDirs) {
    foreach($classDirs as $classDir) {
      $this->collectClassesForDir($classDir);
    }
    return $this->classes;
  }

  protected function getFQN($className) {
    if(empty($className) || $className{0} == '\\')
      return $className;

    if(!empty($this->uses[$className])) {
      return $this->uses[$className];
    }
    if(empty($this->namespace) || $this->namespace == '\\')
      return '\\'.$className;
    return $this->namespace.'\\'.$className;
  }

  protected function fixFQN(&$className) {
    $className = $this->getFQN($className);
  }

  public function storeClasses($filename) {
    file_put_contents($filename, gzencode(serialize([$this->interfaces,$this->classes]), 9));
    //echo "size: ".filesize($filename)."\n";

  }

  public function loadClasses($filename) {
    $contents = gzdecode(file_get_contents($filename));
    $arr = unserialize($contents);
    $this->interfaces = $arr[0];
    $this->classes = $arr[1];
  }

}
