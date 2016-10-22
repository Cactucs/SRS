<?php
/**
 * Date: 19.2.13
 * Time: 9:42
 * Author: Michal Májský
 */

namespace SRS\Form\Evidence;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    \SRS\Form\EntityForm,
    \SRS\Helpers;

/**
 * Formular "O mne" v profilu
 */
class AboutForm extends EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbuser)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addTextArea('about', 'O mně');

        if ($dbuser->displayArrivalDeparture()) {
            $this->addText('arrival', 'Příjezd')
                ->setAttribute('class', 'datetimepicker')
                ->addCondition(Form::FILLED)
                ->addRule(Form::PATTERN, 'Datum a čas příjezdu není ve správném tvaru', Helpers::DATETIME_PATTERN);

            $this->addText('departure', 'Odjezd')
                ->setAttribute('class', 'datetimepicker')
                ->addCondition(Form::FILLED)
                ->addRule(Form::PATTERN, 'Datum a čas odjezdu není ve správném tvaru', Helpers::DATETIME_PATTERN);
        }

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Doplňující informace uloženy', 'success');
        $this->presenter->redirect('this');
    }
}
