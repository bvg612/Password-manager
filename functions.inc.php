<?php

/**
 * @param string|null $param
 *
 * @return \Config
 */
function getConfig($param = null) {
  $config = \Config::getInstance();
  if(!empty($param))
    return $config->getVar($param);

  return $config;
}

function array_splice_assoc(&$input, $offset, $length, $replacement) {
  $replacement = (array) $replacement;
  $key_indices = array_flip(array_keys($input));
  if(isset($input[$offset]) && is_string($offset)) {
    $offset = $key_indices[$offset];
  }
  if(isset($input[$length]) && is_string($length)) {
    $length = $key_indices[$length] - $offset;
  }

  $input = array_slice($input, 0, $offset, true)
    + $replacement
    + array_slice($input, $offset + $length, null, true);
}



