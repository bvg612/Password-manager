<?php

namespace nsfw\database;

use \Exception;

class dbException extends Exception{

  public $numRows = 0;

  protected $dbQuery = '';

  /**
   * dbException constructor.
   * @param string $message
   * @param int $errno
   * @param Exception|null $previous
   * @param string $query
   */
  public function __construct($message = null, $errno = 0, Exception $previous = null, $query = '-- unknown') {
    $this->dbQuery = $query;
    parent::__construct($message, $errno, $previous);
  }

  /**
   * @ToDo Make sure this method provides similar output as the one for \Exception
   *
   * @return string
   */
  public function __toString() {
//    $trace='';
////    var_dump($this->getTrace());
//    foreach($this->getTrace() as $key=>$value){
//      if(!array_key_exists('file', $value))
//        $value['file'] = '<i>undefined</i>';
//      if(!array_key_exists('line', $value))
//        $value['line'] = 0;
//      if(isset($value['class'])){
//        $function=$value['class'].$value['type'].$value['function'];
//      }else{
//        $function=$value['function'];
//      }
//      $trace.=' '.$key.'# '.$value['file'].'(line '.$value['line'].'): '.$function."\n";
//    }

    return 'Uncaught exception "'.get_class($this).'":('.$this->getCode().') with message "'.$this->getMessage().'" in '.$this->getFile().':'.$this->getLine()
        ."\nQuery: ".$this->dbQuery."\n"
        ."Stack trace:\n".$this->getTraceAsString();

//    return __CLASS__ . ': ['.$this->code.']: '.$this->message."\nQuery: ".$this->dbQuery."\nStack trace:\n".$this->getTraceAsString();
  }

  /**
   * @return string The query that caused the exception
   */
  public function getQuery(){
    return $this->dbQuery;
  }

}
