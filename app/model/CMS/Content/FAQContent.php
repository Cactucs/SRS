<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="faq_content")
 */
class FAQContent extends Content
{
    protected $type = Content::FAQ;
}