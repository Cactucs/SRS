<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="news_content")
 */
class NewsContent extends Content
{
    protected $type = Content::NEWS;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $count;
}