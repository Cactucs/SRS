<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

class ProgramsContentControl extends Control
{
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ProgramsContentControl.latte');

        //$template->text = $content->getText();

        $template->render();
    }
}