<?php

namespace nsfw\forms;

interface FormLoader {
  /**
   * @param Form $form
   */
  function loadForm(Form $form);
}
