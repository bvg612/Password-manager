<?php

namespace nsfw\template;

interface DisplayObject {
  /**
   * @return string
   */
  function getHtml();

  /**
   * @return string
   */
  function __toString();
}
