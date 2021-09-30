<?php
/**
 * User: npelov
 * Date: 11-05-17
 * Time: 12:03 PM
 */

namespace nsfw\session;


use Exception;
use nsfw\database\Database;
use nsfw\database\dbException;
use SessionHandlerInterface;

class DbHandler implements SessionHandlerInterface{
  /** @var bool Deprecated! For databases that used old sessions table. see updateDb();*/
  public static $autoUpgrade = true;

  protected $db = null;
  protected $dbId = 0;
  protected $dbTable = 'sessions';
  protected $autoCreateTable = true;
  /** @var int */
  protected $gcLivetimeOverride;


  /**
   * DbHandler constructor.
   * @param null $db
   */
  public function __construct(Database $db, $gcLivetimeOverride = null) {
    $this->db = $db;
    $this->gcLivetimeOverride = $gcLivetimeOverride;
  }


  public function upgradeDb($table){
    $db = $this->db;
    $rows = $db->queryRows('describe `'.$table.'`');
    foreach($rows as $row){
      if($row['Field'] == 'ts'){
        if(strToLower($row['Type']) == 'timestamp')
          return; // no need to upgrade
        break;
      }
    }
    // must upgrade
    $rows = $db->queryRows('SELECT * FROM `'.$table.'`');
    $this->db->query('DROP TABLE `'.$table.'`');
    $query = self::getCreateQuery();
    $db->query($query);
    foreach($rows as $row){
      $row['ts'] = array('FROM_UNIXTIME('.intval($row['ts']).')');
      $row['last_access'] = array('FROM_UNIXTIME('.intval($row['last_access']).')');
      $db->insert($table, $row);
    }
  }

  function dbCreateTable($table = 'sessions'){
    if(!$this->autoCreateTable)
      return;

    $query = self::getCreateQuery();
    $this->db->query($query);
  }

  public function open($save_path, $session_name){
    $this->dbCreateTable($this->dbTable);
    if(self::$autoUpgrade){
      $this->upgradeDb($this->dbTable);
    }
    $this->db->simpleUpdate($this->dbTable, [
      'sid' => $session_name,
      'last_access' => ['NOW()'],
    ]);
    return true;
  }

  public function close(){
    return true;
  }

  public function read($sid){
    $db = $this->db;
    $row = array('sid'=>$sid, 'last_access'=>array('NOW()'));
    $this->db->simpleUpdate($this->dbTable, $row);
    $row = $db->queryFirstRow('SELECT id,data FROM '.$this->dbTable.' WHERE sid="'.$db->escape($sid).'"');
    if(!$row){
      $this->dbId = 0;
      return '';
    }
    $this->dbId = $row['id'];
    return $row['data'];
  }

  public function write($sid, $sess_data){
    $db = $this->db;

    // Desperate measure to stop search engines to fill database with empty sessions
    if(trim($sess_data) == '') {
      try {
        $db->query('LOCK TABLES '.$this->dbTable.' WRITE');
        $dbId = $db->queryFirstField('SELECT id FROM '.$this->dbTable.' WHERE sid = "'.$db->escape($sid).'" FOR UPDATE');
        if($dbId)
          $db->simpleUpdate($this->dbTable, ['id' => $dbId, 'data' => $sess_data]);

      }catch (dbException $e) {
      } finally {
        $db->query('UNLOCK TABLES');
      }
      return true;
    }
    $ip = 0;
    if(array_key_exists('REMOTE_ADDR', $_SERVER))
      $ip = ip2long($_SERVER['REMOTE_ADDR']);
    $hexIp = sprintf('%08X', $ip);
    $sessionRow = array(
      'sid' => $sid,
      'lastip' => $hexIp,
      'last_access' => array('NOW()'),
      'data' => $sess_data,
    );
    try{
      $db->query('LOCK TABLES '.$this->dbTable.' WRITE');
      $this->dbId = $db->queryFirstField('SELECT id FROM '.$this->dbTable.' WHERE sid = "'.$db->escape($sid).'"');
      if(!$this->dbId){
        $sessionRow['createip'] = $hexIp;
        $sessionRow['ts'] = array('NOW()');
        $this->dbId = $db->insert($this->dbTable, $sessionRow);
      }else{
        $sessionRow['id'] = $this->dbId;
        $db->simpleUpdate($this->dbTable, $sessionRow);
      }
//      $db->query('UNLOCK TABLES');
    }catch(Exception $e){
//      $db->query('UNLOCK TABLES');
      throw $e;
    } finally {
      $db->query('UNLOCK TABLES');
    }
    return true;
  }

  public function destroy($sid){
    $this->db->query('DELETE FROM '.$this->dbTable.' WHERE sid = "'.$this->db->escape($sid).'"');
    return true;
  }

  public function gc($maxlifetime){
    if(!is_null($this->gcLivetimeOverride)){
      $maxlifetime = $this->gcLivetimeOverride;
    }
    $this->db->query('DELETE FROM '.$this->dbTable.' WHERE `last_access` < (NOW() - INTERVAL '.intval($maxlifetime).' SECOND)');
    $this->db->query('DELETE FROM '.$this->dbTable.' WHERE `data` = "" AND `last_access` < (NOW() - INTERVAL 1 HOUR)');
    return true;
  }

  static protected function getCreateQuery($table = 'sessions'){
    return 'CREATE TABLE IF NOT EXISTS `'.$table.'` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sid` varchar(32) NOT NULL,
  `createip` varchar(8) NOT NULL,
  `lastip` varchar(8) NOT NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `last_access` timestamp NULL default NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `sid` (`sid`),
  KEY `ts` (`ts`),
  KEY `createip` (`createip`),
  KEY `lastip` (`lastip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8  ;';
  }


}
