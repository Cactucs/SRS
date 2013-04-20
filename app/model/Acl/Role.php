<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\Acl;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;


/**
 * Entita uzivatelske role
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Acl\RoleRepository")
 *
 * @property-read int $id
 * @property string $name
 * @property bool $system
 * @property bool $registerable
 * @property bool $approvedAfterRegistration
 * @property bool $pays
 * @property integer $fee
 * @property string $feeWord
 * @property bool $syncedWithSkautIS
 * @property \DateTime|string $registerableFrom
 * @property \DateTime|string $registerableTo
 * @property \Doctrine\Common\Collections\ArrayCollection $users
 * @property \Doctrine\Common\Collections\ArrayCollection $permissions
 */
class Role extends \SRS\Model\BaseEntity
{
    const GUEST = 'guest';
    const REGISTERED = 'Registrovaný';
    const ATTENDEE = 'Účastník';
    const SERVICE_TEAM = 'Servis Tým';
    const LECTOR = 'Lektor';
    const ORGANIZER = 'Organizátor';
    const ADMIN = 'Administrátor';

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $name;


    /**
     * @ORM\OneToMany(targetEntity="\SRS\model\User", mappedBy="role")
     * @var mixed
     */
    protected $users;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Permission", inversedBy="roles", cascade={"persist"})
     * @var mixed
     */
    protected $permissions;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\CMS\Page", inversedBy="roles", cascade={"persist"})
     * @var mixed
     */
    protected $pages;


    /**
     * Pokud je role systemova, nelze ji mazat
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $registerable = true;

    /**
     * Je role po registraci rovnou schvalena?
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $approvedAfterRegistration = false;


    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableFrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableTo;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $pays = false;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fee;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $feeWord;


    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $syncedWithSkautIS = true;


    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUsers($users)
    {
        $this->users = $users;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    public function setSystem($system)
    {
        $this->system = $system;
    }

    public function isSystem()
    {
        return $this->system;
    }

    public function getRegisterable()
    {
        return $this->registerable;
    }

    public function setRegisterable($registerable)
    {
        $this->registerable = $registerable;
    }

    public function getApprovedAfterRegistration()
    {
        return $this->approvedAfterRegistration;
    }

    public function setApprovedAfterRegistration($approvedAfterRegistration)
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }


    public function setRegisterableFrom($registerableFrom)
    {
        if (is_string($registerableFrom)) {
            $registerableFrom = new \DateTime($registerableFrom);
        }
        $this->registerableFrom = $registerableFrom;
    }

    public function getRegisterableFrom()
    {
        return $this->registerableFrom;
    }

    public function setRegisterableTo($registerableTo)
    {
        if (is_string($registerableTo)) {
            $registerableTo = new \DateTime($registerableTo);
        }
        $this->registerableTo = $registerableTo;
    }

    public function getRegisterableTo()
    {
        return $this->registerableTo;
    }

    /**
     * @param int $fee
     */
    public function setFee($fee)
    {
        $this->fee = (int) $fee;
    }

    /**
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param string $fee_word
     */
    public function setFeeWord($fee_word)
    {
        $this->feeWord = $fee_word;
    }

    /**
     * @return string
     */
    public function getFeeWord()
    {
        return $this->feeWord;
    }

    /**
     * @param mixed $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param boolean $pays
     */
    public function setPays($pays)
    {
        $this->pays = $pays;
    }

    /**
     * @return boolean
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * @param boolean $syncedWithSkautIS
     */
    public function setSyncedWithSkautIS($syncedWithSkautIS)
    {
        $this->syncedWithSkautIS = $syncedWithSkautIS;
    }

    /**
     * @return boolean
     */
    public function getSyncedWithSkautIS()
    {
        return $this->syncedWithSkautIS;
    }
}

/**
 * Doctrine Repozitar pro entitu Role.
 *
 * Pridava dalsi metody pro vyhledavni roli v databazi
 */
class RoleRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\Acl\Role';

    public function findRegisterableNow()
    {
        $today = new \DateTime('now');
        $today = $today->format('Y-m-d');

        $query = $this->_em->createQuery("SELECT r FROM {$this->entity} r WHERE r.registerable=true
              AND (r.registerableFrom <= '{$today}' OR r.registerableFrom IS NULL)
              AND (r.registerableTo >= '{$today}' OR r.registerableTo IS NULL)");
        return $query->getResult();
    }

    public function findApprovedUsersInRole($roleName)
    {
        $role = $this->_em->getRepository($this->_entityName)->findByName($roleName);
        if ($role == null) throw new RoleException("Role s tímto jménem {$roleName} neexistuje");
        $role = $role[0];
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("approved", 1));
        return $role->users->matching($criteria);
    }
}

class RoleException extends \Exception
{

}
