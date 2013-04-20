<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;


/**
 * Entita reprezentujici stranku webove prezentace
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\CMS\PageRepository")
 *
 *
 * @property-read int $id
 * @property string $name
 * @property string $slug
 * @property bool $public
 * @property \Doctrine\ORM\PersistentCollection $roles
 * @property \Doctrine\Common\Collections\ArrayCollection $contents
 * @property int $position
 */
class Page extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\Column
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $slug;


    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;


    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", inversedBy="pages")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $roles;


    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\CMS\Content", mappedBy="page", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @var  \Doctrine\Common\Collections\ArrayCollection
     */
    protected $contents;


    public function __construct($name, $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contents = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    public function getContents($area = null)
    {

        if ($area == null) {
            return $this->contents;
        }
        if (!in_array($area, Content::$AREA_TYPES)) {
            throw new SRSPageException("Area {$area} není definována");
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("area", $area))
            ->orderBy(array("position" => "ASC"));
        return $this->contents->matching($criteria);
    }

    public function countContents($area)
    {
        if (!in_array($area, Content::$AREA_TYPES)) {
            throw new SRSPageException("Area {$area} není definována");
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("area", $area))
            ->orderBy(array("position" => "ASC"));
        return $this->contents->matching($criteria)->count();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setPublic($public)
    {
        $this->public = $public;
    }

    public function getPublic()
    {
        return $this->public;
    }

    public function isAllowedToRole($roleName)
    {
        return $this->roles->exists(function ($key, $role) use ($roleName) {
            return $role->name == $roleName;
        });


    }
}


class PageRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\CMS\Page';

    public function getCount()
    {
        return $this->_em->createQuery('SELECT count (p.id) FROM ' . $this->entity . ' p')
            ->getSingleScalarResult();
    }

    public function slugToId($slug)
    {
        try {
            return $this->_em->createQuery("SELECT p.id FROM " . $this->entity . " p WHERE p.slug = '{$slug}' ")
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function IdToSlug($id)
    {
        try {
            return $this->_em->createQuery("SELECT p.slug FROM " . $this->entity . " p WHERE p.id = '{$id}' ")
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function findPublishedOrderedByPosition()
    {
        return $this->_em->createQuery("SELECT p FROM " . $this->entity . " p WHERE p.public = '1' ORDER BY p.position ASC ")
            ->getResult();
    }

}

class SRSPageException extends \Exception
{

}
