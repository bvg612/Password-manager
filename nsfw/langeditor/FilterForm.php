<?php
/**
 * User: npelov
 * Date: 17-08-17
 * Time: 10:52 AM
 */

namespace nsfw\langeditor;


use nsfw\forms\CheckboxField;
use nsfw\forms\TemplateForm;

/**
 * Class FilterForm
 * @package nsfw\langeditor
 *
 * @property CheckboxField showOnlyEmpty
 */
class FilterForm extends TemplateForm {

  /**
   * FilterForm constructor.
   */
  public function __construct() {
    parent::__construct();

    /** @var CheckboxField $ff */
    $ff = $this->addNewField('showOnlyEmpty', 'checkbox', 1);
    if(getParam('f_se', false, 'C'))
      $ff->setChecked(true);
    $ff->setLabel('Show only empty');
    $ff->setAttribute('id', 'showOnlyEmpty');
  }
}
