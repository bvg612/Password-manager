<?php
/**
 * User: npelov
 * Date: 31-07-17
 * Time: 4:40 PM
 */

namespace nsfw\i18;


class CyrillicTools {
  public static $encoding = 'UTF-8';
  protected static $cyrToLat = [
    'я' => 'ia',
    'в' => 'v',
    'е' => 'e',
    'р' => 'r',
    'т' => 't',
    'ъ' => 'a',
    'у' => 'u',
    'и' => 'i',
    'о' => 'o',
    'п' => 'p',
    'ш' => 'sh',
    'щ' => 'sht',
    'а' => 'a',
    'с' => 's',
    'д' => 'd',
    'ф' => 'f',
    'г' => 'g',
    'х' => 'h',
    'й' => 'i',
    'к' => 'k',
    'л' => 'l',
    'ю' => 'iu',
    'з' => 'z',
    'ь' => 'i',
    'ц' => 'c',
    'ж' => 'j',
    'б' => 'b',
    'н' => 'n',
    'м' => 'm',
    'ч' => 'ch',

    'Я' => 'Ia',
    'В' => 'V',
    'Е' => 'E',
    'Р' => 'R',
    'Т' => 'T',
    'Ъ' => 'A',
    'У' => 'U',
    'И' => 'I',
    'О' => 'O',
    'П' => 'P',
    'Ш' => 'Sh',
    'Щ' => 'Sht',
    'А' => 'A',
    'С' => 'S',
    'Д' => 'D',
    'Ф' => 'F',
    'Г' => 'G',
    'Х' => 'H',
    'Й' => 'I',
    'К' => 'K',
    'Л' => 'L',
    'Ю' => 'Iu',
    'З' => 'Z',
    'Ь' => 'I',
    'Ц' => 'C',
    'Ж' => 'J',
    'Б' => 'B',
    'Н' => 'N',
    'М' => 'M',
    'Ч' => 'Ch',
  ];

  public static function cyrToLat($cyrStr) {
    $strLen = mb_strlen($cyrStr, self::$encoding);
    $latStr = '';
    for($i = 0; $i<$strLen; ++$i) {
      $letter = mb_substr($cyrStr, $i, 1, self::$encoding);
      if(!empty(self::$cyrToLat[$letter]))
        $letter = self::$cyrToLat[$letter];
      $latStr .= $letter;
    }
    return $latStr;
  }
}
