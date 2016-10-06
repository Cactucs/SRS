<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */



namespace SRS\Form\CMS;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro aktuality
 */
class NewsForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addText('published', 'Zveřejněno:')
            ->addRule(Form::FILLED, 'Zadejte datum zveřejnění')
            ->addRule(FORM::PATTERN, 'Špatný formát datumu zveřejnění', \SRS\Helpers::DATE_PATTERN)
            ->getControlPrototype()->class('datepicker');
        $this->addTextArea('text', 'Text:')
            ->addRule(Form::FILLED, 'Zadejte text')
            ->getControlPrototype()->class('tinyMCE');

//        $this->addText('valid_from', 'Platné od:');
//        $this->addText('valid_to', 'Platné do:');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn space pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $exists = $values['id'] != null;

        if (!$exists) {
            $news = new \SRS\Model\CMS\News();
        } else {
            $news = $this->presenter->context->database->getRepository('\SRS\model\CMS\News')->find($values['id']);
        }

        $news->setProperties($values, $this->presenter->context->database);

        if (!$exists) {
            $this->presenter->context->database->persist($news);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Aktualita upravena', 'success');
        $submitName = ($this->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_continue') $this->presenter->redirect('this', $news->id);
        $this->presenter->redirect(':Back:CMS:News:default');

    }

}