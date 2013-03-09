<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 19.1.13
 * Time: 10:37
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;

class NewsBox extends \Nette\Application\UI\Control
{

    public function render($contentID)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');
        $content = $this->presenter->context->database->getRepository('\SRS\Model\CMS\NewsContent')->find($contentID);

        $this->template->news = $this->presenter->context->database->getRepository('\SRS\model\CMS\News')->findAllOrderedByDate($content->count);

        $template->render();
    }



}