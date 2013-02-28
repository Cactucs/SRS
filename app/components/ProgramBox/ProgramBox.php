<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 4.2.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Components;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;
/**
 * Slouzi pro vyber programu na frontendu
 */
class ProgramBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');
        $template->backlink = $this->presenter->context->httpRequest->url->path;
        $template->isAllowedLogInPrograms = (bool) $this->presenter->context->database->getRepository('\SRS\Model\Settings')->get('is_allowed_log_in_programs');
        $template->userHasPermission = $this->presenter->user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS);

        $template->render();
    }

}
