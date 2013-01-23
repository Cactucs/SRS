<?php

/**
 * Homepage presenter.
 */
namespace FrontModule;

class PagePresenter extends \SRS\BasePresenter
{
    protected $repository;
    protected $pageId;

    public function startup() {
        parent::startup();
        $this->repository = $this->context->database->getRepository('\SRS\Model\CMS\Page');
    }

    public function beforeRender() {
        parent::beforeRender();
        $path = $this->getHttpRequest()->url->path;
        $this->template->backlink = $path;
    }

	public function renderDefault($pageId)
	{
        if ($pageId == null) {
            $httpRequest = $this->context->getService('httpRequest');
            if ($httpRequest->url->path == '/') {
                $page = $this->repository->findBy(array('slug' => '/', 'public' => true));
                if ($page == null) {
                   throw new \Nette\Application\BadRequestException('Stránka se slugem "/" neexistuje nebo není zveřejněná. Vytvořte ji v administriaci.', 404);

                }
                $page = $page[0];

            }


        }
        else {
            $page = $this->repository->find($pageId);
        }

        if (!$page->isAllowedToRole($this->user->roles[0])) {
            throw new \Nette\Application\BadRequestException('Na zobrazení této stránky nemáte práva', 404);
        }
        $this->pageId = $page->id;
        $this->template->documents = $this->context->database->getRepository('\SRS\model\CMS\Documents\Document')->findAll();
        $this->template->page = $page;
	}

    public function createComponentMenu() {
        $pageRepo = $this->context->database->getRepository('\SRS\Model\CMS\Page');
        $menu = new \SRS\Components\Menu($pageRepo);
        return $menu;
    }

    public function createComponentAttendeeBox() {
        return new \SRS\Components\AttendeeBox();
    }

    public function createComponentFaqBox() {
        return new \SRS\Components\FaqBox();
    }

    public function createComponentNewsBox() {
        return new \SRS\Components\NewsBox();
    }

}
