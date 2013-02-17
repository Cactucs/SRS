<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 17.2.13
 * Time: 11:30
 * To change this template use File | Settings | File Templates.
 */
namespace FrontModule;

class ProfilePresenter extends BasePresenter
{
    protected $userRepo;

    protected $skautIS;

    public function startup()
    {
        parent::startup();

        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
        $this->skautIS = $this->context->skautIS;

        if (!$this->context->user->isLoggedIn()) {
            $this->flashMessage('Pro přístup do profilu musíte být přihlášeni', 'error');
            $this->redirect(':Front:Page:default');
        }
    }


    public function renderDefault() {
        /**
         * @var \SRS\Model\User
         */
        $user = $this->userRepo->find($this->context->user->id);
        $skautISPerson = $this->skautIS->getPerson($this->user->identity->token, $user->skautISPersonId);
        $form = $this['profileForm'];
        $form->bindEntity($user);
        $birthday = \explode("T", $skautISPerson->Birthday);
        $skautISPerson->birthdate = $birthday[0];
        $this->template->skautISPerson = $skautISPerson;

    }

    protected function createComponentProfileForm()
    {
        $form = new \SRS\Form\ProfileForm();
        $form->inject($this->context->database, $this->skautIS);
        return $form;
    }

}
