<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\TagRepository;
use App\Services\FilesService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;

class DocumentsGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    /**
     * @var FilesService
     */
    private $filesService;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(Translator $translator, DocumentRepository $documentRepository, TagRepository $tagRepository, FilesService $filesService)
    {
        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
        $this->tagRepository = $tagRepository;
        $this->filesService = $filesService;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/documents_grid.latte');
    }

    public function createComponentDocumentsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->documentRepository->createQueryBuilder('d'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.documents_name');

        $grid->addColumnText('tags', 'admin.cms.documents_tags')
            ->setRenderer(function ($row) {
                $tags = Html::el();
                foreach ($row->getTags() as $tag) {
                    $tags->addHtml(Html::el('span')
                        ->setAttribute('class', 'label label-primary')
                        ->setText($tag->getName()));
                    $tags->addHtml(Html::el()->setText(' '));
                }
                return $tags;
            });

        $grid->addColumnText('file', 'admin.cms.documents_file')
            ->setRenderer(function ($row) {
                return Html::el()
                    ->addHtml(Html::el('span')->setAttribute('class', 'fa fa-download'))
                    ->addText(' ')
                    ->addHtml(Html::el('a')
                        ->setAttribute('href', '../../../files' . $row->getFile())
                        ->setAttribute('target', '_blank')
                         ->addText($this->translator->translate('admin.cms.documents_download'))
                    );
            });

        $grid->addColumnText('description', 'admin.cms.documents_description');

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents_timestamp')
            ->setFormat('j. n. Y H:i');

        $tagsChoices = $this->prepareTagsChoices();

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($tagsChoices) {
            $container->addText('name', '')
                ->addCondition(Form::FILLED) //->addRule(Form::FILLED, 'admin.cms.documents_name_empty') //TODO validace
                ->addRule(Form::IS_NOT_IN, 'admin.cms.documents_name_exists', $this->documentRepository->findAllNames());

            $container->addMultiSelect('tags', '', $tagsChoices)->setAttribute('class', 'datagrid-multiselect');
                //->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');
                //->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) use($tagsChoices) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsChoices)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.cms.documents_name_exists', $this->documentRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'tags' => $this->tagRepository->getTagsIds($item->getTags()),
                'description' => $item->getDescription()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.documents_delete_confirm')
            ]);
    }

    public function add($values) {
        $p = $this->getPresenter();

        $name = $values['name'];
        $tags = $values['tags'];
        $file = $values['file'];
        $description = $values['description'];

        if (!$name) {
            $p->flashMessage('admin.cms.documents_name_empty', 'danger');
        }
        elseif (count($tags) == 0) {
            $p->flashMessage('admin.cms.documents_tags_empty', 'danger');
        }
        elseif ($file->size <= 0) {
            $p->flashMessage('admin.cms.documents_file_empty', 'danger');
        }
        else {
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);
            $this->documentRepository->addDocument($name, $this->tagRepository->findTagsByIds($tags), $path, $description);
            $p->flashMessage('admin.cms.documents_added', 'success');
        }

        $this->redirect('this');
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $file = $values['file'];
        if ($file->name) {
            $this->filesService->delete($this->documentRepository->find($id)->getFile());
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);
        }
        else {
            $path = null;
        }

        $this->documentRepository->editDocument($id, $values['name'], $this->tagRepository->findTagsByIds($values['tags']), $path, $values['description']);
        $p->flashMessage('admin.cms.documents_edited', 'success');

        $this->redirect('this');
    }

    public function handleDelete($id)
    {
        $this->filesService->delete($this->documentRepository->find($id)->getFile());
        $this->documentRepository->removeDocument($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.documents_deleted', 'success');

        $this->redirect('this');
    }

    private function prepareTagsChoices() {
        $choices = [];
        foreach ($this->tagRepository->findTagsOrderedByName() as $tag)
            $choices[$tag->getId()] = $tag->getName();
        return $choices;
    }

    private function generatePath($file) {
        return Document::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}