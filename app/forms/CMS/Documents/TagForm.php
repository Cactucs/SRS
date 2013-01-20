<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


/**
 * Formular pro vytvoreni dokumentu
 */

namespace SRS\Form\CMS\Documents;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class TagForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $roleChoices)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addText('name', 'Jméno:')->getControlPrototype()->class('name')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addSubmit('submit','Uložit')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $tagExists = $values['id'] != null;

        if (!$tagExists) {
        $tag = new \SRS\Model\CMS\Documents\Tag();
        }
        else {
            $tag = $this->presenter->context->database->getRepository('\SRS\Model\CMS\Documents\Tag')->find($values['id']);
        }
        $tag->setProperties($values, $this->presenter->context->database);
        if ($tag->id != null) {
            $this->presenter->flashMessage('Štítek vytvořen', 'success');
        }
        else {
            $this->presenter->flashMessage('Štítek upraven', 'success');
        }
        $this->presenter->context->database->persist($tag);
        $this->presenter->context->database->flush();

        $this->presenter->redirect(':Back:CMS:tags');
    }

}