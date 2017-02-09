<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Program\BlockRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class ProgramBlocksGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var BlockRepository
     */
    private $blockRepository;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(Translator $translator, BlockRepository $blockRepository, SettingsRepository $settingsRepository, UserRepository $userRepository, CategoryRepository $categoryRepository)
    {
        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_blocks_grid.latte');
    }

    public function createComponentProgramBlocksGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')
            ->addSelect('l')->leftJoin('b.lector', 'l')
            ->addSelect('c')->leftJoin('b.category', 'c')
        );
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);


        $grid->addColumnText('name', 'admin.program.blocks_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('category', 'admin.program.blocks_category', 'category.name')
            ->setSortable('c.name')
            ->setFilterMultiSelect($this->categoryRepository->getCategoriesOptions(), 'c.id');

        $grid->addColumnText('lector', 'admin.program.blocks_lector', 'lector.name')
            ->setSortable('l.displayName')
            ->setFilterMultiSelect($this->userRepository->getLectorsOptions(), 'l.id');

        $basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');

        $grid->addColumnText('duration', 'admin.program.blocks_duration')
            ->setRenderer(function ($row) use ($basicBlockDuration) {
                return $this->translator->translate('admin.common.minutes', null, ['count' => $row->getDurationInMinutes($basicBlockDuration)]);
            })
            ->setSortable()
            ->setFilterMultiSelect($this->settingsRepository->getDurationsOptions());

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity')
            ->setRendererOnCondition(function ($row) {
                    return $this->translator->translate('admin.program.blocks_capacity_unlimited');
                }, function ($row) {
                    return $row->getCapacity() === null;
                }
            )
            ->setSortable();

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory');
        $columnMandatory
            ->addOption(false, 'admin.program.blocks_mandatory_voluntary')
                ->setClass('btn-success')
                ->endOption()
            ->addOption(true, 'admin.program.blocks_mandatory_mandatory')
                ->setClass('btn-danger')
                ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $columnMandatory
            ->setFilterSelect([
                '' => $this->translator->translate('admin.common.all'),
                false => $this->translator->translate('admin.program.blocks_mandatory_voluntary'),
                true => $this->translator->translate('admin.program.blocks_mandatory_mandatory')
            ]);

        $grid->addColumnText('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getProgramsCount();
            });


        $grid->addToolbarButton('Blocks:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('detail', 'admin.common.detail', 'Blocks:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', 'admin.common.edit', 'Blocks:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm')
            ]);
    }

    public function handleDelete($id)
    {
        $this->blockRepository->removeBlock($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.blocks_deleted', 'success');

        $this->redirect('this');
    }

    public function changeMandatory($id, $mandatory) {
        $p = $this->getPresenter();

        if (!$p->isUserAllowedModifyProgramBlock($id)) {
            $p->flashMessage('admin.program.blocks_change_mandatory_denied', 'danger');
        }
        else {
            $this->blockRepository->setBlockMandatory($id, $mandatory);
            $p->flashMessage('admin.program.blocks_changed_mandatory', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['programBlocksGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }
}