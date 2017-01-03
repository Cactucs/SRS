<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

class HtmlContentControl extends Control
{
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/html_content.latte');

        //$template->text = $content->getText();

        $template->render();
    }
}