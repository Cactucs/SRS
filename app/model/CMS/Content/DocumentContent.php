<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Document\Tag;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 */
class DocumentContent extends Content
{
    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\CMS\Document\Tag", cascade={"persist"})
     * @var Tag
     */
    protected $tag;
}