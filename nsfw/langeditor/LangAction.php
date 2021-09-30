<?php
/**
 * User: npelov
 * Date: 03-07-17
 * Time: 5:47 PM
 */

namespace nsfw\langeditor;


use nsfw\controller\AbstractAction;
use nsfw\database\Database;
use nsfw\session\Session;

/**
 * Class LangAction
 * @package nsfw\langeditor

 * @property Database $db
 * @property Session $session
 * @property string $langEditorUrl
 *
 */
abstract class LangAction extends AbstractAction {

}
