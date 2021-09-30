<?php

namespace nsfw\session;


interface Session {
  /**
   * @return boolean true if session is started
   */
  function isStarted();

  static public function getInstance($reset = false);

  function start();

  /**
   * Unsets session variable
   * @param $name
   * @return mixed
   */
  function delete($name);

  function has($name);

  function get($name, $default = null);

  function set($name, $value);

  function close();

  function destroy();


}
