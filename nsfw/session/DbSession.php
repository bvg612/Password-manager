<?php

namespace nsfw\session;


use Exception;
use nsfw\database\Database;

class DbSession extends AbstractSession {

  /** @var Database  */
  protected $db = null;
  protected $saveHandler = null;

  public function __construct(){
    parent::__construct();
  }

  public function startDb(Database $database){
    $this->db = $database;
    $this->start();
  }

  public function start(){
    if(empty($this->db))
      throw new Exception('Use startDb() to start DbSession.');
    parent::start();
  }


  function setHandler(){
    $this->saveHandler = new DbHandler($this->db, $this->gcLivetimeOverride);
    if(PHP_VERSION_ID > 50400) {
      session_set_save_handler($this->saveHandler);
    }else {
      $sh = $this->saveHandler;
      session_set_save_handler(
        array(&$sh, 'open'),
        array(&$sh, 'close'),
        array(&$sh, 'read'),
        array(&$sh, 'write'),
        array(&$sh, 'destroy'),
        array(&$sh, 'gc')
      );
    }
  }



}
