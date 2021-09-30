<?php
/**
 * Created by PhpStorm.
 * User: npelov
 * Date: 20-04-16
 * Time: 11:46 AM
 */

namespace nsfw;


class Debug {
  public static function getCallInfo($stepsBack) {
    $bt = debug_backtrace();
    $max = count($bt);
    $bt0 = $bt[$stepsBack];
    $bt[1]['object'] = '';
    $bt[2]['object'] = '';
    $func = '';
    $isObject = !empty($bt0['object']) && is_object($bt0['object']);
    $className = 'function ';
    $file = '?';

    if(!empty($bt0['file']))
      $file = $bt0['file'];

    $line = '';
    if(!empty($bt0['line']))
      $line = $bt0['line'];

    $hasClass = !empty($bt0['class']);

    $callType = '';
    if($isObject) {
      $callType = '->';
    }else {
      if($hasClass)
      $callType = '::';
    }

    if($hasClass) {
      $className = $bt0['class'] . $callType;
    }



    if(!empty($bt0['function']))
      $func = $className.$bt0['function'].'() called at ';
    //debug_print_backtrace();
    return $func.$file.':'.$bt0['line'];

  }
}
