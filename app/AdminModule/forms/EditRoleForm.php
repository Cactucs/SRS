<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\PermissionRepository;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\PageRepository;


use App\Model\Program\ProgramRepository;
use Nette;
use Nette\Application\UI\Form;

class EditRoleForm extends Nette\Object
{
    /** @var Role */
    private $role;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var PageRepository */
    private $pageRepository;

    /** @var PermissionRepository */
    private $permissionRepository;

    /** @var ProgramRepository */
    private $programRepository;

    public function __construct(BaseForm $baseFormFactory, RoleRepository $roleRepository,
                                PageRepository $pageRepository, PermissionRepository $permissionRepository,
                                ProgramRepository $programRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->roleRepository = $roleRepository;
        $this->pageRepository = $pageRepository;
        $this->permissionRepository = $permissionRepository;
        $this->programRepository = $programRepository;
    }

    public function create($id)
    {
        $this->role = $this->roleRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.acl.roles_name')
            ->addRule(Form::FILLED, 'admin.acl.roles_name_empty')
            ->addRule(Form::IS_NOT_IN, 'admin.acl.roles_name_exists', $this->roleRepository->findOthersNames($id))
            ->addRule(Form::NOT_EQUAL, 'admin.acl.roles_name_reserved', 'test');

        $form->addCheckbox('registerable', 'admin.acl.roles_registerable_form');

        $form->addDateTimePicker('registerableFrom', 'admin.acl.roles_registerable_from')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_from_note'));

        $form->addDateTimePicker('registerableTo', 'admin.acl.roles_registerable_to')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_to_note'));

        $form->addText('capacity', 'admin.acl.roles_capacity')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_capacity_note'))
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.acl.roles_capacity_format')
            ->addRule(Form::MIN, 'admin.acl.roles_capacity_low', $this->roleRepository->countApprovedUsersInRole($this->role));

        $form->addCheckbox('approvedAfterRegistration', 'admin.acl.roles_approved_after_registration');

        $form->addCheckbox('syncedWithSkautIs', 'admin.acl.roles_synced_with_skaut_is');

        $form->addCheckbox('displayArrivalDeparture', 'admin.acl.roles_display_arrival_departure');

        $form->addText('fee', 'admin.acl.roles_fee')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.acl.roles_fee_format');

        $form->addMultiSelect('permissions', 'admin.acl.roles_permissions', $this->preparePermissionsOptions());


        $pagesOptions = $this->pageRepository->getPagesOptions();

        $allowedPages = $form->addMultiSelect('pages', 'admin.acl.roles_pages', $pagesOptions);

        $form->addSelect('redirectAfterLogin', 'admin.acl.roles_redirect_after_login', $pagesOptions)
            ->setPrompt('')
            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_redirect_after_login_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRedirectAllowed'], 'admin.acl.roles_redirect_after_login_restricted', [$allowedPages]);


        $rolesOptions = $this->roleRepository->getRolesWithoutRoleOptions($this->role->getId());

        $incompatibleRolesSelect = $form->addMultiSelect('incompatibleRoles', 'admin.acl.roles_incompatible_roles', $rolesOptions);

        $requiredRolesSelect = $form->addMultiSelect('requiredRoles', 'admin.acl.roles_required_roles', $rolesOptions);

        $incompatibleRolesSelect
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.acl.roles_incompatible_collision', [$incompatibleRolesSelect, $requiredRolesSelect]);

        $requiredRolesSelect
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.acl.roles_required_collision', [$incompatibleRolesSelect, $requiredRolesSelect]);

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        $form->setDefaults([
            'id' => $id,
            'name' => $this->role->getName(),
            'registerable' => $this->role->isRegisterable(),
            'registerableFrom' => $this->role->getRegisterableFrom(),
            'registerableTo' => $this->role->getRegisterableTo(),
            'capacity' => $this->role->getCapacity(),
            'approvedAfterRegistration' => $this->role->isApprovedAfterRegistration(),
            'syncedWithSkautIs' => $this->role->isSyncedWithSkautIS(),
            'displayArrivalDeparture' => $this->role->isDisplayArrivalDeparture(),
            'fee' => $this->role->getFee(),
            'permissions' => $this->permissionRepository->findPermissionsIds($this->role->getPermissions()),
            'pages' => $this->pageRepository->findPagesSlugs($this->role->getPages()),
            'redirectAfterLogin' => $this->role->getRedirectAfterLogin(),
            'incompatibleRoles' => $this->roleRepository->findRolesIds($this->role->getIncompatibleRoles()),
            'requiredRoles' => $this->roleRepository->findRolesIds($this->role->getRequiredRoles())
        ]);


        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        if (!$form['cancel']->isSubmittedBy()) {
            $capacity = $values['capacity'] !== '' ? $values['capacity'] : null;

            $this->role->setName($values['name']);
            $this->role->setRegisterable($values['registerable']);
            $this->role->setRegisterableFrom($values['registerableFrom']);
            $this->role->setRegisterableTo($values['registerableTo']);
            $this->role->setCapacity($capacity);
            $this->role->setApprovedAfterRegistration($values['approvedAfterRegistration']);
            $this->role->setSyncedWithSkautIS($values['syncedWithSkautIs']);
            $this->role->setDisplayArrivalDeparture($values['displayArrivalDeparture']);
            $this->role->setFee($values['fee']);
            $this->role->setPermissions($this->permissionRepository->findPermissionsByIds($values['permissions']));
            $this->role->setPages($this->pageRepository->findPagesBySlugs($values['pages']));
            $this->role->setRedirectAfterLogin($values['redirectAfterLogin']);
            $this->role->setIncompatibleRoles($this->roleRepository->findRolesByIds($values['incompatibleRoles']));
            $this->role->setRequiredRoles($this->roleRepository->findRolesByIds($values['requiredRoles']));

            $this->roleRepository->save($this->role);

            $this->programRepository->updateUsersPrograms($this->role->getUsers());
            $this->roleRepository->save($this->role);
        }
    }

    private function preparePermissionsOptions()
    {
        $options = [];

        $groupWebName = 'common.permission_group.web';
        $optionsGroupWeb = &$options[$groupWebName];
        $this->preparePermissionOption($optionsGroupWeb, Permission::CHOOSE_PROGRAMS, Resource::PROGRAM);

        $groupAdminName = 'common.permission_group.admin';
        $optionsGroupAdmin = &$options[$groupAdminName];
        $this->preparePermissionOption($optionsGroupAdmin, Permission::ACCESS, Resource::ADMIN);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE, Resource::CMS);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::ACCESS, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE_OWN_PROGRAMS, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE_ALL_PROGRAMS, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE_SCHEDULE, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE_CATEGORIES, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE_ROOMS, Resource::PROGRAM);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE, Resource::USERS);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE, Resource::ACL);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE, Resource::MAILING);
        $this->preparePermissionOption($optionsGroupAdmin, Permission::MANAGE, Resource::CONFIGURATION);

        return $options;
    }

    private function preparePermissionOption(&$optionsGroup, $permissionName, $resourceName) {
        $permission = $this->permissionRepository->findByPermissionAndResourceName($permissionName, $resourceName);
        $optionsGroup[$permission->getId()] = 'common.permission_name.' . $permissionName . '.' . $resourceName;
    }

    public function validateIncompatibleAndRequiredCollision($field, $args) {
        $incompatibleRoles = $this->roleRepository->findRolesByIds($args[0]);
        $requiredRoles = $this->roleRepository->findRolesByIds($args[1]);

        $this->roleRepository->getEntityManager()->getConnection()->beginTransaction();

        $this->role->setIncompatibleRoles($incompatibleRoles);
        $this->role->setRequiredRoles($requiredRoles);

        $valid = true;

        foreach ($this->roleRepository->findAll() as $role) {
            foreach ($role->getRequiredRolesTransitive() as $requiredRole) {
                if ($role->getIncompatibleRoles()->contains($requiredRole)) {
                    $valid = false;
                    break;
                }
            }
            if (!$valid)
                break;
        }

        $this->roleRepository->getEntityManager()->getConnection()->rollBack();

        return $valid;
    }

    public function validateRedirectAllowed($field, $args) {
        return in_array($field->getValue(), $args[0]);
    }
}
