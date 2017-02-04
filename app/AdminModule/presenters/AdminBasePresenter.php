<?php

namespace App\AdminModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\ResourceRepository;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use App\Services\SkautIsService;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

abstract class AdminBasePresenter extends BasePresenter
{
    /**
     * @var ResourceRepository
     * @inject
     */
    public $resourceRepository;

    /**
     * @var RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SkautIsService
     * @inject
     */
    public $skautIsService;

    /**
     * @var User
     */
    protected $dbuser;

    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('admin');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('admin');
    }

    public function startup()
    {
        parent::startup();

        if ($this->user->isLoggedIn() && !$this->skautIsService->isLoggedIn())
            $this->user->logout(true);

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('admin.common.login_required', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
        if (!$this->user->isAllowed(Resource::ADMIN, Permission::ACCESS)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }

        $this->dbuser = $this->userRepository->findUserById($this->user->id);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->resourceACL = Resource::ACL;
        $this->template->resourceCMS = Resource::CMS;
        $this->template->resourceConfiguration = Resource::CONFIGURATION;
        $this->template->resourceUsers = Resource::USERS;
        $this->template->resourceMailing = Resource::MAILING;
        $this->template->resourceProgram = Resource::PROGRAM;

        $this->template->permissionAccess = Permission::ACCESS;
        $this->template->permissionManage = Permission::MANAGE;
        $this->template->permissionManageOwnPrograms = Permission::MANAGE_OWN_PROGRAMS;
        $this->template->permissionManageAllPrograms = Permission::MANAGE_ALL_PROGRAMS;
        $this->template->permissionManageSchedule = Permission::MANAGE_SCHEDULE;
        $this->template->permissionManageRooms = Permission::MANAGE_ROOMS;
        $this->template->permissionManageCategories = Permission::MANAGE_CATEGORIES;

        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->seminarName = $this->settingsRepository->getValue('seminar_name');

        $this->template->sidebarVisible = false;

        $this->template->settings = $this->settingsRepository;
    }
}