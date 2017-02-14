<?php

namespace App\AdminModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\Enums\PaymentType;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

class UsersGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var CustomInputRepository
     */
    private $customInputRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(Translator $translator, UserRepository $userRepository,
                                SettingsRepository $settingsRepository, CustomInputRepository $customInputRepository,
                                RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->customInputRepository = $customInputRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }

    public function createComponentUsersGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();

        $grid->addGroupAction('Change order status', [
            1 => 'Received',
            2 => 'Ready',
            3 => 'Processing',
            4 => 'Sent',
            5 => 'Storno'
        ])->onSelect[] = [$this, 'groupChangeStatus'];
//
//        $grid->addGroupSelectAction('Send', [
//            'john'  => 'John',
//            'joe'   => 'Joe',
//            'frank' => 'Frank'
//        ])->onSelect[] = [$this, 'groupSend'];
//
//        $grid->addGroupMultiSelectAction('SendMulti', [
//            'john'  => 'John',
//            'joe'   => 'Joe',
//            'frank' => 'Frank'
//        ])->onSelect[] = [$this, 'groupSendaaaaa'];

        $grid->addColumnText('displayName', 'admin.users.users_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('username', 'admin.users.users_username')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('roles', 'admin.users.users_roles', 'roles')
            ->setRenderer(function ($row) {
                $roles = [];
                foreach ($row->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            })
            ->setFilterMultiSelect($this->roleRepository->getRolesOptions())
            ->setCondition(function($qb, $values) {
                $qb->join('u.roles', 'r')->where('r.id IN (:ids)')->setParameter(':ids', $values);
            });

        $columnApproved = $grid->addColumnStatus('approved', 'admin.users.users_approved');
        $columnApproved
            ->addOption(false, 'admin.users.users_approved_unapproved')
                ->setClass('btn-danger')
                ->endOption()
            ->addOption(true, 'admin.users.users_approved_approved')
                ->setClass('btn-success')
                ->endOption()
            ->onChange[] = [$this, 'changeApproved'];

        $columnApproved
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_approved_unapproved',
                '1' => 'admin.users.users_approved_approved'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unit', 'admin.users.users_membership')
            ->setRendererOnCondition(function ($row) {
                    return Html::el('span')
                        ->style('color: red')
                        ->setText($row->isMember() ?
                            $this->translator->translate('admin.users.users_membership_no') :
                            $this->translator->translate('admin.users.users_membership_not_connected'));
                }, function ($row) {
                    return $row->getUnit() === null;
                }
            )
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(function($qb, $sort) {
                $sort = $sort['age'] == 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sort);
            });

        $grid->addColumnText('city', 'admin.users.users_city')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'admin.users.users_fee');

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method') //TODO editace
            ->setSortable()
            ->setFilterSelect($this->preparePaymentOptions())
            ->setTranslateOptions();

        $variableSymbolCode = $this->settingsRepository->getValue('variable_symbol_code');
        $grid->addColumnText('variableSymbol', 'admin.users.users_variable_symbol')
            ->setRenderer(function ($row) use($variableSymbolCode) {
                return $row->getVariableSymbolWithCode($variableSymbolCode);
            })
            ->setSortable();

        $grid->addColumnDateTime('paymentDate', 'admin.users.users_payment_date') //TODO editace
            ->setSortable();

        $grid->addColumnDateTime('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date')
            ->setSortable();

        $grid->addColumnDateTime('firstLogin', 'admin.users.users_first_login')
            ->setSortable();

        $columnAttended = $grid->addColumnStatus('attended', 'admin.users.users_attended');
        $columnAttended
            ->addOption(false, 'admin.users.users_attended_no')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.users.users_attended_yes')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeAttended'];

        $columnAttended
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_attended_no',
                '1' => 'admin.users.users_attended_yes'
            ])
            ->setTranslateOptions();

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $grid->addColumnText('customInput' . $customInput->getId(), $customInput->getName())
                ->setRenderer(function ($row) use ($customInput) {
                    $customInputValue = $row->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        if ($customInputValue->getInput()->getType() == CustomInput::TEXT)
                            return $customInputValue->getValue();
                        else {
                            return $customInputValue->getValue() ?
                                $this->translator->translate('admin.common.yes') :
                                $this->translator->translate('admin.common.no');
                        }
                    }
                    return null;
                })
                ->setSortable();
        }

        $grid->addAction('detail', 'admin.common.detail', 'Users:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', 'admin.common.edit', 'Users:edit');

        $grid->setColumnsSummary(['fee']);
    }

    public function changeApproved($id, $approved) {
        $user = $this->userRepository->findById($id);

        $user->setApproved($approved);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_approved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    public function changeAttended($id, $attended) {
        $user = $this->userRepository->findById($id);

        $user->setAttended($attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    private function preparePaymentOptions() {
        $options = [];
        $options[''] = 'admin.common.all';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }
}