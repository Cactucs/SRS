<?php

namespace BackModule;
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 5.2.13
 * Time: 16:36
 * To change this template use File | Settings | File Templates.
 */
class ConfigurationPresenter extends BasePresenter
{

    public function startup() {
        parent::startup();

    }

    public function renderDefault() {

    }

    public function renderSkautIS() {
        $skautISSeminarID = $this->dbsettings->get('skautis_seminar_id');
        $skautISSeminarName = $this->dbsettings->get('skautis_seminar_name');

        $this->template->seminarID = $skautISSeminarID;
        $this->template->seminarName = $skautISSeminarName;

    }

    public function handleDisconnectEvent()
    {
        $this->dbsettings->set('skautis_seminar_id', '');
        $this->dbsettings->set('skautis_seminar_name', '');
        $this->flashMessage('Systém odpojen od skautIS akce', 'success');
        $this->redirect('this');
    }

    public function handleSyncParticipants()
    {
        $usersToSync = $this->context->database->getRepository('\SRS\Model\User')->findAllForSkautISSync();
        try {
            $count = $this->context->skautIS->syncParticipants($this->user->identity->token, $this->dbsettings->get('skautis_seminar_id'), $usersToSync);
            $this->flashMessage("Do skautIS bylo vloženo {$count} účastníků");
        } catch (\SoapFault $e)
        {
            $this->flashMessage('Synchronizace se nezdařila. Je pravděpodobné, že pro provedení synchronizace nemáte patřičná práva. Požádejte o synchronizaci uživatele, který akci propojil se skautIS', 'error forever');
        }

        $this->redirect('this');
    }

    public function handleClearCache() {
        $options = array('command' => 'srs:cc');
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput($options);
        $this->context->console->application->setAutoExit(false);

        $this->context->console->application->run($input, $output);
        $this->flashMessage('Cache promazána');
        $this->redirect('this');
    }


    protected function createComponentSettingsForm() {
        return new \SRS\Form\Configuration\SettingsForm(null, null, $this->dbsettings, $this->context->parameters);
    }

    protected function createComponentSkautISEventForm()
    {
        try {
            $events = $this->context->skautIS->getEvents($this->user->identity->token);
        } catch (\SoapFault $e)
        {
           $events = array();
        }

        return new \SRS\Form\Configuration\SkautISEventForm(null, null, $events);
    }



}
