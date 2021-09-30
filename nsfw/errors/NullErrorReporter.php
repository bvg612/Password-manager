<?php

namespace nsfw\errors;


use nsfw\Singleton;

class NullErrorReporter extends AbstractErrorReporter {

  public static function getInstance() {
    static $instance;
    if(empty($instance))
      $instance = new static();
    return $instance;
  }

  function addErrors($errors, $vars = []) {
  }

  function getErrors() {
    return [];
  }

  function clearErrors() {
  }

  function hasErrors() {
    return false;
  }

  function setErrors($errors) {
  }

  function errorRedirect($url, $errors, $vars = []) {
    httpRedirect($url);
  }

  function addInfoMessages($msgs, $vars = []) {
  }

  function setInfoMessages($msgs) {
  }

  function getInfoMessages() {
    return [];
  }

  function clearInfoMessages() {
  }

  function hasInfoMessages() {
    return false;
  }

  function infoRedirect($url, $infoMessages, $vars = []) {
    httpRedirect($url);
  }

  function popErrors() {
  }

  function pushErrors() {
  }

  function popInfoMessages() {
  }

  function pushInfoMessages() {
  }

  function getInfoHtml($clear = true) {
    return '';
  }

  function getErrorsHtml($clear = true) {
    return '';
  }

  function getHtml($clear = true) {
    return '';
  }



}
