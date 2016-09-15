<?php
/**
 * Date: 18.2.13
 * Time: 10:16
 * Author: Michal Májský
 */
namespace SRS\Form\Evidence;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro upravu udaju ucastnika na detailu
 */
class EvidenceEditForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $configParams, $database)
    {
        parent::__construct($parent, $name);

        $roles = $database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $rolesGrid = array();
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST) {
                $rolesGrid[$role->id] = $role->name;
            }
        }

        $this->addHidden('id');

        $checkRolesCapacity = function($field, $database) {
            $values = $field->getValue();
            $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->usersLimit !== null) {
                    if ($role->usersLimit < count($role->users) || (!$user->isInRole($role->name) && $role->usersLimit == count($role->users)))
                        return false;
                }
            }
            return true;
        };

        $checkRolesCombination = function($field, $database) {
            $values = $field->getValue();

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->name == Role::REGISTERED && count($values) != 1)
                    return false;
            }
            return true;
        };

        $checkRolesEmpty = function($field) {
            $values = $field->getValue();
            return count($values) != 0;
        };

        $this->addMultiSelect('roles', 'Role')->setItems($rolesGrid)
            ->setAttribute('size', count($rolesGrid))
            ->addRule($checkRolesCapacity, 'Kapacita role byla překročena.', $database)
            ->addRule($checkRolesCombination, 'Role "Nepřihlášený" nemůže být kombinována s jinou rolí.', $database)
            ->addRule($checkRolesEmpty, 'Musí být přidělena alespoň jedna role.', $database);

        $this->addCheckbox('approved', 'Schválený');

        $this->addCheckbox('attended', 'Přítomen');

        $this->addSelect('paymentMethod', 'Platební metoda')->setItems($configParams['payment_methods'])->setPrompt('Nezadáno');

        $this->addText('variableSymbol', 'Variabilní symbol')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::INTEGER);

        $this->addText('paymentDate', 'Datum zaplacení')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::PATTERN, 'Datum zaplacení není ve správném tvaru', \SRS\Helpers::DATE_PATTERN);

        $this->addText('incomeProofPrintedDate', 'Příjmový doklad vytištěn dne')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::PATTERN, 'Datum vytištění příjmového dokladu není ve správném tvaru', \SRS\Helpers::DATE_PATTERN);

        $CUSTOM_BOOLEAN_COUNT = $configParams['user_custom_boolean_count'];
        for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
            $column = 'user_custom_boolean_' . $i;
            $propertyName = 'customBoolean' . $i;
            $this->addCheckbox($propertyName, $column);
        }

        $CUSTOM_TEXT_COUNT = $configParams['user_custom_text_count'];
        for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
            $column = 'user_custom_text_' . $i;
            $propertyName = 'customText' . $i;
            $this->addText($propertyName, $column);
        }

        $this->addTextArea('note', 'Neveřejné poznámky');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
        $this['paymentDate']->getControlPrototype()->class('datepicker');
        $this['incomeProofPrintedDate']->getControlPrototype()->class('datepicker');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);

        $code = $this->presenter->context->database->getRepository('\SRS\Model\Settings')->get('variable_symbol_code');
        if ($user->generateVariableSymbol($code) == $values['variableSymbol'])
            $values['variableSymbol'] = null;

        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Záznam uložen', 'success');
        $this->presenter->redirect('this');
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}
