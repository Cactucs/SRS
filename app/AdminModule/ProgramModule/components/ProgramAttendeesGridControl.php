<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\Permission;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Ublaboo\DataGrid\DataGrid;

class ProgramAttendeesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;

    public function __construct(Translator $translator, ProgramRepository $programRepository,
                                UserRepository $userRepository, Session $session)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->programRepository = $programRepository;
        $this->userRepository = $userRepository;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_attendees_grid.latte');
    }

    public function createComponentProgramAttendeesGrid($name)
    {
        $programId = $this->getPresenter()->getParameter('programId');
        if (!$programId)
            $programId = $this->sessionSection->programId;

        $program = $this->programRepository->findById($programId);

        $grid = new DataGrid($this, $name);


        $grid->setTranslator($this->translator);

        $qb = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.programs', 'p', 'WITH', 'p.id = :pid')
            ->innerJoin('u.roles', 'r')
            ->innerJoin('r.permissions', 'per')
            ->where('u.approved = true')
            ->andWhere('per.name = :permission')
            ->setParameter('pid', $programId)
            ->setParameter('permission', Permission::CHOOSE_PROGRAMS)
            ->orderBy('u.displayName');

        if ($program && $program->getBlock()->getCategory()) {
            $qb = $qb
                ->innerJoin('u.roles', 'rol')
                ->innerJoin('rol.registerableCategories', 'c')
                ->andWhere('c.id = :cid')
                ->setParameter('cid', $program->getBlock()->getCategory()->getId());
        }

        $grid->setDataSource($qb);


        $grid->addGroupAction('Přihlásit')->onSelect[] = [$this, 'deleteExamples'];
        $grid->addGroupAction('Odhlásit')->onSelect[] = [$this, 'doSomethingElse'];

        $grid->addColumnText('displayName', 'admin.program.blocks_attendees_name');

        $grid->addColumnText('attends', 'admin.program.blocks_attendees_attends', 'pid')
            ->setRenderer(function ($item) use ($program) {
                return $item->getPrograms()->contains($program) ? 'Ano' : 'Ne';
            });

        $grid->addFilterSelect('attends', 'Status:', ['' => 'Vše', 1 => 'Ano', 0 => 'Ne'])
            ->setCondition(function ($qb, $value) use ($program) {
                if ($value === '')
                    return;
                elseif ($value == 1)
                    $qb->innerJoin('u.programs', 'pro')
                        ->andWhere('pro.id = :proid')
                        ->setParameter('proid', $program->getId());
                elseif ($value == 0)
                    $qb->leftJoin('u.programs', 'pro')
                        ->andWhere('(pro.id != :proid OR pro.id IS NULL)')
                        ->setParameter('proid', $program->getId());
            });

        $grid->setDefaultFilter(['attends' => 1], false);

        $grid->addAction('register', 'Přihlásit', 'register!')
            ->setClass('btn btn-xs btn-success')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm')
            ]);
        $grid->allowRowsAction('register', function($item) use($program) {
            return !$program->isAttendee($item);
        });

        $grid->addAction('unregister', 'Odhlásit', 'unregister!')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm')
            ]);
        $grid->allowRowsAction('unregister', function($item) use($program) {
            return $program->isAttendee($item);
        });
    }

    public function handleRegister($id)
    {
        //TODO
//        $block = $this->blockRepository->findById($id);
//        $this->blockRepository->remove($block);
//
//        $this->getPresenter()->flashMessage('admin.program.blocks_deleted', 'success');
//
//        $this->redirect('this');
    }

    public function handleUnregister($id)
    {
        //TODO
//        $block = $this->blockRepository->findById($id);
//        $this->blockRepository->remove($block);
//
//        $this->getPresenter()->flashMessage('admin.program.blocks_deleted', 'success');
//
//        $this->redirect('this');
    }
}