<?php

namespace nsfw\forms;


interface ValidatorBypass {
  /**
   * Returns trie if validation should be skipped, false if not.
   *
   * @param mixed $caller Whatever class called the Bypass. Usually FormField
   * @return mixed
   */
  function skipValidation($caller = null);
}
