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
 * Formular pro spravu FAQ v administraci
 */
class FaqForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addTextArea('question', 'Otázka')
            ->addRule(Form::FILLED, 'Zadejte otázku');
        $this->addTextArea('answer', 'Odpověď')->getControlPrototype()->class('tinyMCE');
        $this->addCheckbox('public', 'Zveřejnit');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn space pull-right');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $faqExists = $values['id'] != null;

        if (!$faqExists) {
            $faq = new \SRS\Model\CMS\Faq();
        } else {
            $faq = $this->presenter->context->database->getRepository('\SRS\model\CMS\Faq')->find($values['id']);
        }

        $faq->setProperties($values, $this->presenter->context->database);

        if (!$faqExists) {
            $this->presenter->context->database->persist($faq);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Otázka upravena', 'success');
        $submitName = ($this->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_continue') $this->presenter->redirect('this', $faq->id);
        $this->presenter->redirect(':Back:CMS:Faq:default');

    }

}