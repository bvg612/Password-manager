<?php

namespace nsfw\errors;
use Exception;
use nsfw\exception\UserException;
use nsfw\session\DbSession;
use nsfw\template\CascadedTemplate;

/**
 * Class SessionErrorReporter
 * @package nsfw\errors
 *
 * @ToDo: test this class - not critical
 */
class ErrorReporter extends AbstractErrorReporter{
  protected $session;
  protected $errors = [];
  protected $infoMessages = [];
  protected $parser;
  protected $tpl;
  protected $infoTpl;
  protected $errorsTpl = '<div class="errorMessages"><ul><block lines><li>{%error}</li></block></ul></div>';
  protected $infoMessagesTpl = '<div class="infoMessages"><ul><block lines><li>{%message}</li></block></ul></div>';
  protected $autoStoreToSession = true;
  /**
   * @var callable[] User handler must return true if handled the exception
   */
  protected $userHandlers = [];
  /** @var bool */
  private $debug = false;

  /**
   * SessionErrorReporter constructor.
   * @param bool $autoStoreToSession
   */
  public function __construct($autoStoreToSession = true) {
    $this->autoStoreToSession = $autoStoreToSession;
    //$this->loadFromSession();
    $this->session = DbSession::getInstance();
    if($this->autoStoreToSession) {
      $this->popInfoMessages();
      $this->popErrors();
    }

    $this->parser = new CascadedTemplate();
    $this->tpl = new CascadedTemplate();
    $this->tpl->setTemplate($this->errorsTpl);
    $this->infoTpl = new CascadedTemplate();
    $this->infoTpl->setTemplate($this->infoMessagesTpl);
  }

  /**
   * @param callable $userHandler A function that excepts two parameters Exception $e, $redirectUrl = false. Must return
   * true if error is handled (in which case the error handling stops or false if not (to continue running other
   * handlers)
   */
  public function addUserHandler($userHandler) {
    $this->userHandlers[] = $userHandler;
  }

  public function removeUserHandler($userHandler) {
    foreach ($this->userHandlers as $index=>$uh) {
      if ($uh === $userHandler)
        unset($this->userHandlers[$index]);
    }
  }

//  /**
//   * Stores errors in session and clears them. Done automatically at destruct, but can be called manually to force it.
//   */
//  public function storeToSession() {
//    if(!$this->hasErrors())
//      return;
//    $_SESSION['errors'] = $this->getClearErrors();
//  }
//
//  /**
//   * Loads errors from session and clears session errors. Done automatically at construct.
//   */
//  public function loadFromSession() {
//    if(empty($_SESSION['errors']))
//      return;
//
//    $this->errors = $_SESSION['errors'];
//    unset($_SESSION['errors']);
//
//  }

  protected function processError($error, $vars) {
    $processed = preg_replace_callback('/\\{%([0-9a-z_\\.-]+)\\}/i',function ($matches) use ($vars){
      if(empty($matches[1]))
        return '';
      $varName = $matches[1];
      if(array_key_exists($varName, $vars))
        return $vars[$varName];
      return '';
    }, $error);
    return $processed;
  }

  /**
   * @param array|iErrorReporter|string $errors
   * @param array $vars
   */
  public function addErrors($errors, $vars = []) {

    if(is_string($errors))
      $errors = [$errors];

    if($errors instanceof iErrorReporter)
      $errors = $errors->getClearErrors();

    foreach($errors as $error){
      $this->errors[] = $this->processError($error, $vars);
    }
  }

  public function getErrors() {
    return $this->errors;
  }

  public function clearErrors() {
    $this->errors = [];
  }

  public function hasErrors() {
    return !empty($this->errors);
  }

  function setErrors($errors) {
    if(is_string($errors))
      $errors = [$errors];

    if($errors instanceof iErrorReporter)
      $errors = $errors->getClearErrors();

    $this->errors = $errors;
  }

  function addInfoMessages($msgs, $vars = []) {
    if(is_string($msgs))
      $msgs = [$msgs];

    if($msgs instanceof iErrorReporter)
      $msgs = $msgs->getClearErrors();

    foreach($msgs as $error){
      $this->infoMessages[] = $this->processError($error, $vars);
    }
  }

  function setInfoMessages($msgs) {
    $this->infoMessages = $msgs;
  }

  function getInfoMessages() {
    return $this->infoMessages;
  }

  function clearInfoMessages() {
    $this->infoMessages = [];
  }

  function hasInfoMessages() {
    return !empty($this->infoMessages);
  }

  function errorRedirect($url, $errors, $vars = []) {
    $this->addErrors($errors);
    $this->pushErrors();
    httpRedirect($url);
    exit;
  }

  function infoRedirect($url, $infoMessages, $vars = []) {
    $this->addInfoMessages($infoMessages, $vars);
    $this->pushInfoMessages();
    httpRedirect($url);
    exit;
  }

  public function popErrors(){
    if(!isset($_SESSION['errorReporterErrors']))
      return;
    $this->errors = $this->errors + ((array)$_SESSION['errorReporterErrors']);
    //var_dump($this->errors); exit;
    unset($_SESSION['errorReporterErrors']);
  }

  public function pushErrors(){
    $_SESSION['errorReporterErrors'] = $this->errors;
  }

  public function popInfoMessages(){
    if(!isset($_SESSION['errorReporterInfoMessages']))
      return;
    //$this->infoMessages = $this->infoMessages + ((array)$_SESSION['errorReporterInfoMessages']);
    $this->infoMessages = ((array)$_SESSION['errorReporterInfoMessages']);
    unset($_SESSION['errorReporterInfoMessages']);
  }

  public function pushInfoMessages(){
    $_SESSION['errorReporterInfoMessages'] = $this->infoMessages;
  }


  function getErrorsHtml($clear = true) {
    $errors = $this->errors;
    if(!$this->hasErrors())
      return '';

    $line = $this->tpl->getBlock('lines');
    foreach($errors as $error){
      $line = $line->appendRow(array('error'=>$error));
    }

    $content = $this->tpl->getParsed();
    //$this->tpl->reset();  // ToDo: implement reset method and uncomment this
    return $content;
  }

  function getInfoHtml($clear = true) {
    $infoMessages = $this->infoMessages;

    if(!$this->hasInfoMessages())
      return '';

    $line = $this->infoTpl->getBlock('lines');
    $line->clearRows();
    foreach($infoMessages as $message){
      $line->appendRow(array('message'=>$message));
    }

    $content = $this->infoTpl->getParsed();
    //$this->infoTpl->reset(); // ToDo: implement reset method and uncomment this
    if($clear)
      $this->clearInfoMessages();
    return $content;
  }

  function getHtml($clear = true) {
    if($this->hasErrors())
      return $this->getErrorsHtml($clear);

    if($this->hasInfoMessages())
      return $this->getInfoHtml($clear);
    return '';
  }


  public function __destruct() {
    if($this->autoStoreToSession) {
      $this->pushErrors();
      $this->pushInfoMessages();
    }
  }


  protected function callUserHandlers($e, $redirectUrl = false) {
    foreach ($this->userHandlers as $userHandler) {
      if (!empty($userHandler) && is_callable($userHandler)) {
        if(call_user_func($userHandler, $this, $e, $redirectUrl)) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * @param Exception $e
   * @param bool $redirectUrl
   */
  public function handleException(Exception $e, $redirectUrl = false) {
    if ($this->callUserHandlers($e, $redirectUrl)) {
      return;
    }
    //var_dump(get_class($e), $e->getMessage());exit;
    if($e instanceof UserException) {
      $this->addErrors($e->getMessage());
    }else {
      /** @var Exception $e */
      if($this->debug)
        $this->addErrors($e->getMessage());
      else
        $this->addErrors('Internal error. We are working on it and it will be fixed soon!');
      // ToDo: Log error properly
      $exception = $e;
      while($exception) {
        $msg = 'Uncaught exception "'.get_class($exception).'" ('.$exception->getCode().') with message "'.
          $exception->getMessage().'" in '.$exception->getFile().':'.$exception->getLine();
        error_log(strval($exception));
        $exception = $exception->getPrevious();
      }
    }

    $this->redirect($redirectUrl);
  }

  /**
   * @param string|false $redirectUrl String url to redirect to or false to ignore redirection
   */
  public function redirect($redirectUrl) {
    if (empty($redirectUrl))
      return;
    $this->pushErrors();
    httpRedirect($redirectUrl);
  }

  /**
   * @param bool $debug
   */
  public function setDebug($debug) {
    $this->debug = $debug;
  }
}
