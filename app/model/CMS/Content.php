<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"content" = "Content", "textcontent" = "TextContent", "documentcontent" = "DocumentContent", "attendeeboxcontent" = "AttendeeBoxContent", "htmlcontent" = "HTMLContent",  "faqcontent" = "FaqContent", "newscontent" = "NewsContent", "programboxcontent" = "ProgramBoxContent"})
 * @property int $position
 * @property string $area
 * @property \SRS\Model\CMS\Page $page
 */
abstract class Content extends \SRS\Model\BaseEntity implements IContent
{
    /**
     * Klic musi odpovidat nazvu tridy bez Content, tzn xxxContent
     * @var array
     *
     */
    public static $TYPES = array(
        'Text' => 'Text',
        'Document' => 'Dokumenty',
        'AttendeeBox' => 'Přihlašovací formulář',
        'HTML' => 'HTML box',
        'Faq' => 'FAQ',
        'News' => 'Aktuality',
        'ProgramBox' => 'Výběr programů'
    );

    public static $AREA_TYPES = array(
        'main',
        'sidebar',
    );

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Jednoznacny identifikator typu contentu
     * @var string
     */
    protected $contentType;

    /**
     * Human readable jmeno contentu pro zobrazeni v adminu apod
     * @var string
     */
    protected $contentName = '';

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $area;


    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\CMS\Page", inversedBy="contents", cascade={"persist"})
     * @var \SRS\Model\CMS\Page
     */
    protected $page;


    public function setPosition($order)
    {
        $this->position = $order;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPage($page)
    {
        $this->page = $page;
    }

    public function getPage()
    {
        return $this->page;
    }


    public function getContentType() {
        return $this->contentType;
    }


    public function setEntityManager($em) {
        $this->em = $em;
    }

    public function getArea() {
        return $this->area;
    }

    public function setArea($areaKey) {
        $this->area = $areaKey;
    }


    /**
     * @return string
     */
    public function getFormIdentificator() {
        return "{$this->contentType}_{$this->id}";
    }

    public function addFormItems(\Nette\Application\UI\Form $form) {
        $formContainer = $form->addContainer($this->getFormIdentificator());
        $formContainer->addHidden('id')->setDefaultValue($this->id)->getControlPrototype()->class('id');
        $formContainer->addHidden('position')->getControlPrototype()->class('order');
        $formContainer->addHidden('contentName')->setDefaultValue($this->contentName)->getControlPrototype()->class('content-name');
        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];
        $this->position = $values['position'];
    }

    public function getContentName() {
        return $this->contentName;
    }


}
