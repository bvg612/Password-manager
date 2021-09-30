<?php

namespace nsfw\langeditor\action;

use nsfw\controller\AbstractAction;

class test extends AbstractAction {

  function runEnd() {
    //$tpl = $this->createCenterTemplate('test.html');
    $mainTpl = $this->pageController->getTemplate();
    $mainTpl->center = '<br />this is a test for action that is not index<br /> is logged: '.var_export($this->langEditor->isLogged(), true);
  }

}
