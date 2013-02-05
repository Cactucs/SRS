<?php

namespace SRS\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\Entity(repositoryClass="\SRS\Model\UserRepository")
 *
 * @property-read int $id
 * @property-read string $username
 * @property string $email
 * @property \SRS\Model\Acl\Role $role
 * @property \Doctrine\Common\Collections\ArrayCollection $programs

 * @property string $firstName
 * @property string $lastName
 * @property string $nickName
 * @property string $sex
 * @property string $birthdate
 * @property int $skautISUserId
 * @property int $skautISPersonId
 * @property bool approved
 */
class User extends BaseEntity
{

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $username;
    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $email;

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Role", inversedBy="users")
     * @var \SRS\Model\Acl\Role
     */
    protected $role;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\Model\Program\Program", mappedBy="attendees", cascade={"persist"})
     */
    protected $programs;


    /**
     * @ORM\Column(type="boolean")
     */
    protected $approved = True;


//    protected $roles;

     /**
     * @ORM\Column
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $nickName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $sex;

    /**
     * @ORM\Column(type="date")
     * @var string
     */
    protected $birthdate;


   /**
    * @var int
    * @ORM\Column(type="integer", unique=true)
   */
    protected $skautISUserId;

    /**
     * @var int
     * @ORM\Column(type="integer", unique=true)
     */
    protected $skautISPersonId;


    /**
     * @param string
     * @return User
     */
    public function __construct($username)
    {
        $this->username = static::normalizeString($username);
        //$this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * @param string $birhdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return string
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getRole() {
        return $this->role;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function getPrograms() {
        return $this->programs;
    }

    public function setPrograms($programs) {
        $this->programs = $programs;
    }

    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    /**
     * @return string
     */
    public function getNickName()
    {
        return $this->nickName;
    }


    /**
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = static::normalizeString($email);
        return $this;
    }

    /**
     * @return string
     */
//    public function getRoles()
//    {
//        return $this->roles;
//    }
//
//    /**
//     * @param mixed
//     * @return User
//     */
//    public function setRoles($roles)
//    {
//        $this->roles = $roles;
//    }

    /**
     * @param $skautISPersonId
     */
    public function setSkautISPersonId($skautISPersonId)
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    /**
     * @return int
     */
    public function getSkautISPersonId()
    {
        return $this->skautISPersonId;
    }

    /**
     * @param $skautISUserId
     */
    public function setSkautISUserId($skautISUserId)
    {
        $this->skautISUserId = $skautISUserId;
    }

    /**
     * @return int
     */
    public function getSkautISUserId()
    {
        return $this->skautISUserId;
    }

    /**
     * @param string
     * @return string
     */
    protected static function normalizeString($s)
    {
        $s = trim($s);
        return $s === "" ? NULL : $s;
    }

    public function hasOtherProgram($program, $basicBlockDuration) {
        foreach ($this->programs as $otherProgram) {
            if ($otherProgram->id == $program->id) continue;
            if ($otherProgram->start == $program->start) return true;
            if ($otherProgram->start > $program->start && $otherProgram->start < $program->countEnd($basicBlockDuration)) return true;
            if ($otherProgram->countEnd($basicBlockDuration) > $program->start && $otherProgram->countEnd($basicBlockDuration) < $program->countEnd($basicBlockDuration) ) return true;
            if ($otherProgram->start < $program->start && $otherProgram->countEnd($basicBlockDuration) > $program->countEnd($basicBlockDuration) ) return true;
        }
        return false;
    }
}


class UserRepository extends \Nella\Doctrine\Repository
{
//    public function findInRole($roleName)
//    {
//        return $this->_em->findAllBy(array('role.name' => $roleName));
//    }

  
}