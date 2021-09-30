<?php
/**
 * User: npelov
 * Date: 20-07-17
 * Time: 9:45 PM
 */

namespace nsfw\ip;


use nsfw\cache\Cache;
use nsfw\cache\NullCache;
use nsfw\database\Database;
use nsfw\Singleton;

/**
 * Class IP2Location
 * @package nsfw\ip
 *
 * @method static IP2Location getInstance(Database $db = null, Cache $cache = null);
 * @method static IP2Location newInstance(Database $db = null, Cache $cache = null);
 */
class IP2Location  extends Singleton {
  /** @var Database */
  protected $db;
  /** @var Cache */
  protected $cache;


  /**
   * IP2Location constructor.
   * @param Database $db
   * @param Cache|null $cache
   */
  public function __construct(Database $db, Cache $cache = null) {
    if(empty($cache))
      $cache = NullCache::createInstance();

    $this->db = $db;
    $this->cache = $cache->getInstance('ip2loc');
  }

  public function queryInfo($ip) {
    $ipLong = sprintf("%u",ip2long($ip));
    $db = $this->db;
    $rows = $db->queryRows('
      SELECT *,if('.$ipLong.' between `ip_from` and `ip_to`, 1, 0) as `ipmatch`
      FROM `ip2location_db11`
     WHERE  
    ip_to >= '.$ipLong.'
      order by `ip_to` asc  limit 10
    ');
    foreach($rows as $row) {
      if($row['ipmatch']) {
        unset($row['ipmatch']);
        return IPInfo::createFromDbRow($row);
      }
    }
    return false;
  }

  /**
   * @param string $ip
   * @return IPInfo|null
   */
  public function getInfo($ip){
    $db = $this->db;
    $info = $this->cache->getSet($ip, [$this, 'queryInfo'],[$ip], 2592000); // 30 days
    if(empty($info))
      return null;

    return $info;

  }
}
