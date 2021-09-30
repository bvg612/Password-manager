<?php
/**
 * User: npelov
 * Date: 20-07-17
 * Time: 9:51 PM
 */

namespace nsfw\ip;


use nsfw\database\DbRow;

/**
 * Class IPInfo
 * @package nsfw\ip
 *
 * @method static IPInfo createFromDbRow(array $row);
 */
class IPInfo extends DbRow {
  public  $ipFrom;
  public  $ipTo;
  public  $countryCode;
  public  $countryName;
  public  $regionName;
  public  $cityName;
  public  $latitude;
  public  $longitude;
  public  $zipCode;
  public  $timeZone;

  /**
   * IPInfo constructor.
   */
  public function __construct() {
    $this->setConvert([
      'ip_from' => 'ipFrom',
      'ip_to' => 'ipTo',
      'country_code' => 'countryCode',
      'country_name' => 'countryName',
      'region_name' => 'regionName',
      'city_name' => 'cityName',
      'zip_code' => 'zipCode',
      'time_zone' => 'timeZone',

    ]);
  }


}
