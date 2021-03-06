<?php

namespace App\AdminModule\MailingModule\Presenters;


use App\AdminModule\MailingModule\Components\IMailHistoryGridControlFactory;

class HistoryPresenter extends MailingBasePresenter
{
    /**
     * @var IMailHistoryGridControlFactory
     * @inject
     */
    public $mailHistoryControlGridFactory;

    protected function createComponentMailHistoryGrid($name)
    {
        return $this->mailHistoryControlGridFactory->create();
    }
}