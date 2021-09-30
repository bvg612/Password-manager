<?php

namespace action;

use nsfw\controller\AbstractAction;


class del_record extends AbstractAction {


  function runEnd() {
    $ns = ns(); $db = $ns->db; $er = $this->errorReporter;

//    $tpl = $this->createCenterTemplate('del_record.html');

    $id = (int)getParam('id', 0, 'P');
    if(empty($id))
      $this->errorReporter->errorRedirect('/', 'Record does not exist');
    try {
      $db->query('DELETE FROM records WHERE id = ' . $id);

      $er->infoRedirect('/', 'Record was deleted');
    }catch (\Exception $e) {
      $er->handleException($e, '/');
    }
  }

}
