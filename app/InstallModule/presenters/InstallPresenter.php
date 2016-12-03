<?php


namespace InstallModule;
use SRS\Model\Acl\Role; //TODO
error_reporting(0);

/**
 * Obsluhuje instalacniho pruvodce
 */
class InstallPresenter extends \App\BaseComponentsPresenter //TODO
{

    public function renderDefault()
    {
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }


        if ($this->context->parameters['database']['installed']) {
            $this->flashMessage('Připojení k databázi již bylo nakonfigurováno');
            $this->redirect(':Install:install:schema');
        }

    }

    public function renderSchema()
    {
        if (!$this->context->parameters['database']['installed']) {
            $this->flashMessage('nejprve nastavte připojení k databázi');
            $this->redirect(':Install:install:default');
        }
        try {
            if ($this->context->parameters['database']['schema_imported'] == true) {
                $this->flashMessage('Schéma databáze bylo již naimportováno');
                $this->redirect(':Install:install:skautIS');
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            //do nothing
        }


    }

    public function handleImportDB()
    {

        $success = true;
        try {
            $options = array('command' => 'orm:schema:create');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->setAutoExit(false);
            $this->context->console->application->run($input, $output);


        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            $this->flashMessage('Nahrání schéma databáze se nepodařilo', 'error');
            $this->flashMessage('Je pravděpodobné, že Databáze již existuje');
            $this->flashMessage($e->getCode() . ': ' . $e->getMessage());
            $success = false;
        }

        try {
            //role
            $options = array('command' => 'srs:initial-data:acl');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

            //settings
            $options = array('command' => 'srs:initial-data:settings');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

            //cms
            $options = array('command' => 'srs:initial-data:cms');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

        } catch (\Doctrine\DBAL\DBALException $e) {
            $success = false;
            $this->template->error = $e->getCode();
            $this->flashMessage('Nahrání inicializačních dat se nepodařilo', 'error');
            $this->flashMessage($e->getCode() . ': ' . $e->getMessage());
        }


        if ($success == true) {
            $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
            $isDebug = $config['common']['parameters']['debug'];
            $environment = $isDebug == true ? 'development' : 'production';
            $config["{$environment} < common"]['parameters']['database']['schema_imported'] = true;
            $configFile = \Nette\Utils\Neon::encode($config, \Nette\Utils\Neon::BLOCK);
            $configUploaded = \file_put_contents(APP_DIR . '/config/config.neon', $configFile);
            $this->flashMessage('Import schématu databáze a inicializačních dat proběhl úspěšně', 'success');
            $this->redirect(':Install:install:skautIS');
        }
        $this->redirect('this');
    }

    public function renderSkautIS()
    {
        if (!$this->context->parameters['database']['installed']) {
            $this->redirect(':Install:install:default');
        }

        if (!$this->context->parameters['database']['schema_imported']) {
            $this->redirect(':Install:install:schema');
        }


        $dbsettings = $this->context->database->getRepository('\SRS\Model\Settings');
        if ($this->context->parameters['skautis']['app_id'] != null) {
            $this->flashMessage('Skaut IS byl již nastaven');
            $this->redirect(':Install:install:admin');
        }

    }

    public function renderAdmin()
    {
        if (!$this->context->parameters['database']['installed']) {
            $this->redirect(':Install:install:default');
        }
        if ($this->context->parameters['skautis']['app_id'] == null) {
            $this->redirect(':Install:install:skautIS');
        }

        if (!$this->context->parameters['database']['schema_imported']) {
            $this->redirect(':Install:install:schema');
        }

        if ($this->context->database->getRepository('\SRS\model\Settings')->get('superadmin_created') == true) {
            $this->flashMessage('Administrátorská role byla již nastavena dříve');
            $this->redirect(':Install:install:finish?before=true');
        }
        if ($this->user->isLoggedIn()) {
            $adminRole = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => Role::ADMIN));
            if ($adminRole == null) {
                throw new \Nette\Application\BadRequestException($message = 'Administrátorská role neexistuje!', $code = 500);
            }
            $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->user->id);
            if ($user == null) {
                throw new \Nette\Application\BadRequestException($message = 'Uživatel je sice přihlášen ale v DB neexistuje!', $code = 500);
            }
            $user->removeRole(Role::REGISTERED);
            $user->addRole($adminRole);
            $this->context->database->flush();
            $this->user->logout(true);
            $this->context->database->getRepository('\SRS\model\Settings')->set('superadmin_created', '1');
            $this->flashMessage('Administrátorská role nastavena', 'success');

            $this->redirect(':Install:install:finish');
        }
        $this->template->backlink = $this->backlink();
    }

    public function renderFinish()
    {
        if (!$this->context->parameters['database']['installed']) {
            $this->redirect(':Install:install:default');
        }
        if (!$this->context->database->getRepository('\SRS\Model\Settings')->get('superadmin_created')) {
            $this->redirect(':Install:install:admin');
        }

        if ($this->context->parameters['skautis']['app_id'] == null) {
            $this->redirect(':Install:install:skautIS');
        }

        if (!$this->context->parameters['database']['schema_imported']) {
            $this->redirect(':Install:install:schema');
        }


        $this->template->installedEarlier = $this->getParameter('before');
    }


    public function IsDBConnection($dbname, $host, $user, $password)
    {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $dbh = new \PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }

    protected function createComponentDatabaseForm()
    {
        return new \SRS\Form\Install\DatabaseForm();
    }

    protected function createComponentSkautISForm()
    {
        return new \SRS\Form\Install\SkautISForm();
    }

}
