<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\RoleRepository;
use App\Model\Program\CategoryRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

class ProgramCategoriesGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(Translator $translator, CategoryRepository $categoryRepository, RoleRepository $roleRepository)
    {
        $this->translator = $translator;
        $this->categoryRepository = $categoryRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_categories_grid.latte');
    }

    public function createComponentProgramCategoriesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.categories_name');

        $grid->addColumnText('registerableRoles', 'admin.program.categories_registerable_roles')
            ->setRenderer(function ($row) {
                $roles = array();
                foreach ($row->getRegisterableRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            });

        $rolesChoices = $this->roleRepository->getRolesWithoutGuestsOptions();

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($rolesChoices) {
            $container->addText('name', '')
                ->addCondition(Form::FILLED) //->addRule(Form::FILLED, 'admin.program.categories_name_empty') //TODO validace
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findAllNames());

            $container->addMultiSelect('registerableRoles', '', $rolesChoices)->setAttribute('class', 'datagrid-multiselect');
                //->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) use($rolesChoices) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty');

            $container->addMultiSelect('registerableRoles', '', $rolesChoices)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findOthersNames($item->getId()));

            $rolesIds = array_map(function($o) { return $o->getId(); }, $item->getRegisterableRoles()->toArray());

            $container->setDefaults([
                'name' => $item->getName(),
                'registerableRoles' => $rolesIds
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.categories_delete_confirm')
            ]);
    }

    public function add($values) {
        $p = $this->getPresenter();

        $name = $values['name'];
        $roles = $values['registerableRoles'];

        if (!$name) {
            $p->flashMessage('admin.program.categories_name_empty', 'danger');
        }
        elseif (count($roles) == 0) {
            $p->flashMessage('admin.program.categories_registerable_roles_empty', 'danger');
        }
        else {
            $this->categoryRepository->addCategory($name, $this->roleRepository->findRolesByIds($roles));
            $p->flashMessage('admin.program.categories_added', 'success');
        }

        $this->redirect('this');
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $this->categoryRepository->editCategory($id, $values['name'], $this->roleRepository->findRolesByIds($values['registerableRoles']));
        $p->flashMessage('admin.program.categories_edited', 'success');

        $this->redirect('this');
    }

    public function handleDelete($id)
    {
        $this->categoryRepository->removeCategory($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.categories_deleted', 'success');

        $this->redirect('this');
    }
}