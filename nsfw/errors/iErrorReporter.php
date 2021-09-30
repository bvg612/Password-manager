<?php

namespace nsfw\errors;


interface iErrorReporter {
  /**
   * @param string|array|iErrorReporter $errors one or more errors
   * @param array $vars
   */
  function addErrors($errors, $vars = []);

  /**
   * @param string|array|iErrorReporter $errors one or more errors
   * @return mixed
   */
  function setErrors($errors);

  /**
   * @return array Errors in an array
   */
  function getErrors();

  /**
   * Same as getErrors(), but clears errors after returning them;
   *
   * @return array Errors in an array
   */
  function getClearErrors();

  /**
   * Removes all errors
   */
  function clearErrors();


  /**
   * @return bool True if there are any errors, false if there aren't
   */
  function hasErrors();

  /**
   * @param string $url
   * @param string|array|iErrorReporter $errors
   * @param array $vars
   * @return
   */
  function errorRedirect($url, $errors, $vars = []);


  /**
   * @param string|array|iErrorReporter $msgs one or more errors
   * @param array $vars
   */
  function addInfoMessages($msgs, $vars = []);

  /**
   * @param string|array|iErrorReporter $msgs one or more errors
   * @return mixed
   */
  function setInfoMessages($msgs);

  /**
   * @return array info messages in an array
   */
  function getInfoMessages();

  /**
   * Same as getErrors(), but clears errors after returning them;
   *
   * @return array Info messages in an array
   */
  function getClearInfoMessages();

  /**
   * Removes all info messages
   */
  function clearInfoMessages();

  /**
   * @return bool True if there are any info messages, false if there aren't
   */
  function hasInfoMessages();

  /**
   * @param string $url
   * @param string|array|iErrorReporter $infoMessages
   * @param array $vars
   * @return
   */
  function infoRedirect($url, $infoMessages, $vars = []);

  /**
   * Loads errors from session
   */
  function popErrors();

  /**
   * Stores the errors to the session
   */
  function pushErrors();

  /**
   * Loads errors from session
   */
  function popInfoMessages();

  /**
   * Stores the errors to the session
   */
  function pushInfoMessages();


  /**
   * @return string
   */
  function getInfoHtml($clear = true);
  /**
   * @return string
   */
  function getErrorsHtml($clear = true);
  /**
   * @return string
   */
  function getHtml($clear = true);

}
