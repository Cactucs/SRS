<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\PageRepository;
use App\Model\Settings\SettingsRepository;
use App\Services\FilesService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

class SettingsForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var PageRepository */
    private $pageRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var FilesService */
    private $filesService;

    public function __construct(BaseForm $baseFormFactory, PageRepository $pageRepository,
                                SettingsRepository $settingsRepository, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->settingsRepository = $settingsRepository;
        $this->filesService = $filesService;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addUpload('logo', 'admin.cms.settings_new_logo')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.settings_new_logo_format');

        $form->addText('footer', 'admin.cms.settings_footer');

        $form->addSelect('redirectAfterLogin', 'admin.cms.settings_redirect_after_login', $this->pageRepository->getPagesOptions())
            ->addRule(Form::FILLED, 'admin.cms.settings_redirect_after_login_empty');

        $form->addCheckbox('displayUsersRoles', 'admin.cms.settings_display_users_roles');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->settingsRepository->getValue('footer'),
            'redirectAfterLogin' => $this->settingsRepository->getValue('redirect_after_login'),
            'displayUsersRoles' => $this->settingsRepository->getValue('display_users_roles')
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $logo = $values['logo'];
        if ($logo->size > 0) {
            $this->filesService->delete('/logo/' . $this->settingsRepository->getValue('logo'));

            $logoName = Strings::webalize($logo->name, '.');
            $this->filesService->save($logo, '/logo/' . $logoName);
            $this->filesService->resizeImage('/logo/' . $logoName, null, 100);

            $this->settingsRepository->setValue('logo', $logoName);
        }

        $this->settingsRepository->setValue('footer', $values['footer']);
        $this->settingsRepository->setValue('redirect_after_login', $values['redirectAfterLogin']);
        $this->settingsRepository->setValue('display_users_roles', $values['displayUsersRoles']);
    }
}
