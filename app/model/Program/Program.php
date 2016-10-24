<?php
/**
 * Date: 26.1.13
 * Time: 13:47
 * Author: Michal Májský
 */
namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * Entita programu zarazeneho v harmonogramu
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\ProgramRepository")
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("none")
 * @property \SRS\Model\Program\Block $block
 * @property \Doctrine\Common\Collections\ArrayCollection $attendees
 * @property \DateTime $start
 * @property integer $duration
 * @property boolean $mandatory
 */
class Program extends \SRS\Model\BaseEntity
{


    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Program\Block", inversedBy="programs")
     * @JMS\Type("integer")
     * @JMS\Accessor(getter="getBlockId")
     *
     *
     */
    protected $block;


    /**
     * @ORM\ManyToMany(targetEntity="\SRS\Model\User", inversedBy="programs", cascade={"persist"})
     * @JMS\Type("ArrayCollection<SRS\Model\User>")
     * @JMS\Exclude
     */
    protected $attendees;

    /**
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;


    /**
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Exclude
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    protected $mandatory = false;

    /**
     * @JMS\Type("DateTime")
     */
    protected $end;

    /**
     * @JMS\Type("string")
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\SerializedName("allDay")
     */
    protected $allDay = false;

    /**
     * @JMS\Type("boolean")
     */
    public $attends;

    /**
     * @JMS\Type("array<integer>")
     */
    public $blocks;

    /**
     * @JMS\Type("integer")
     */
    public $attendeesCount;


    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    public function setBlock($block)
    {
        $this->block = $block;
    }

    public function getBlock()
    {
        return $this->block;
    }

    public function getBlockId()
    {
        if ($this->block != null)
            return $this->block->id;
        return null;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    public function getMandatory()
    {
        return $this->mandatory;
    }

    public function setStart($start)
    {
        $this->start = $start;

    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function setEnd($end)
    {
        $this->end = $end;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }


    public function getAllday()
    {
        return $this->allDay;
    }

    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;
    }

    /**
     * @param string $basicDuration
     * @return \DateTime
     */
    public function countEnd($basicDuration)
    {
        if ($this->block == null) {
            $minutes = $basicDuration * $this->duration;
        } else {
            $minutes = $basicDuration * $this->block->duration;
        }
        $end = $clone = clone $this->start;
        $end->modify("+ {$minutes} minutes");
        return $end;
    }

    public function hasAttendee($user)
    {
        return $this->attendees->contains($user);
    }

    public function prepareForJson($user = null, $basicDuration, $blocks)
    {
        $this->end = $this->countEnd($basicDuration);
        $this->attendeesCount = $this->attendees->count();

        if ($user != null) {
            $this->attends = $this->hasAttendee($user);
            $this->blocks = $blocks;
        }

        if ($this->block != null) {
            $this->title = $this->block->name;
        } else {
            $this->title = "(Nepřiřazeno)";
        }
    }

    public function getUnsignedUsers($roles)
    {
        $unsignedUsers = array();
        foreach ($roles as $role) {
            foreach ($role->users as $user) {
                if (!$this->hasAttendee($user)) {
                    $unsignedUsers[] = $user;
                }
            }
        }
        return $unsignedUsers;
    }

    public function countAttendees() {
        return count($this->attendees);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamp()
    {
        $this->timestamp = new \DateTime('now');
    }
}

/**
 * Vlastni repozitar pro praci s programy. Obsahuje metody pro serializaci a deseraliazci do json
 */
class ProgramRepository extends \Nella\Doctrine\Repository
{
    private function getProgramsInSameTime($program, $basicBlockDuration)
    {
        $programs = $this->_em->getRepository($this->_entityName)->findAll();
        $otherPrograms = [];

        foreach ($programs as $otherProgram) {
            if ($otherProgram->id == $program->id) continue;

            if ($otherProgram->start == $program->start) {
                $otherPrograms[] = $otherProgram->id;
                continue;
            }

            if ($otherProgram->start > $program->start && $otherProgram->start < $program->countEnd($basicBlockDuration)) {
                $otherPrograms[] = $otherProgram->id;
                continue;
            }

            if ($otherProgram->countEnd($basicBlockDuration) > $program->start && $otherProgram->countEnd($basicBlockDuration) < $program->countEnd($basicBlockDuration)) {
                $otherPrograms[] = $otherProgram->id;
                continue;
            }

            if ($otherProgram->start < $program->start && $otherProgram->countEnd($basicBlockDuration) > $program->countEnd($basicBlockDuration)) {
                $otherPrograms[] = $otherProgram->id;
                continue;
            }
        }

        return $otherPrograms;
    }

    private function getProgramsSameBlock($program)
    {
        $programs = $this->_em->getRepository($this->_entityName)->findAll();
        $samePrograms = [];

        foreach ($programs as $otherProgram) {
            if ($otherProgram->id == $program->id)
                continue;
            if ($otherProgram->block == null || $program->block == null)
                continue;
            if ($otherProgram->block->id == $program->block->id)
                $samePrograms[] = $otherProgram->id;
        }

        return $samePrograms;
    }

    public function findAllForJson($basicDuration, $user = null, $dbuser = null, $onlyAssigned = false, $userAllowed = false)
    {
        if ($onlyAssigned == false) {
            $programs = $this->_em->getRepository($this->_entityName)->findAll();
        } else {
            $query = $this->_em->createQuery("SELECT p FROM {$this->_entityName} p WHERE p.block IS NOT NULL");
            $programs = $query->getResult();
        }

        foreach ($programs as $program) {
            $blocks = [];
            if ($dbuser != null) {
                $blocks = array_merge($this->getProgramsSameBlock($program), $this->getProgramsInSameTime($program, $basicDuration));
            }
            $program->prepareForJson($dbuser, $basicDuration, $blocks);
        }

        if ($userAllowed) {
            $allowedPrograms = array();

            $categories = array();
            $categories[] = null;

            $roles = $user->getIdentity()->getRoles();

            foreach ($roles as $role) {
                $dbrole = $this->_em->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => $role));

                foreach ($dbrole->registerableCategories as $category) {
                    $categories[] = $category;
                }
            }

            foreach ($programs as $program) {
                if (in_array($program->block->category, $categories))
                    $allowedPrograms[] = $program;
            }

            return $allowedPrograms;
        }

        return $programs;
    }

    public function saveFromJson($data, $basicBlockDuration)
    {
        $data = json_decode($data);
        $data = (array)$data;

        json_last_error();
        $data['start'] = $data['startJSON'];
        if (isset($data['block']->id)) {
            $data['block'] = $data['block']->id;
        }

        $exists = isset($data['id']);
        if ($exists == true) {
            $program = $this->_em->getRepository($this->_entityName)->find($data['id']);
        } else {
            $program = new \SRS\Model\Program\Program();

        }
        $start = \DateTime::createFromFormat("Y-n-j G:i:s", $data['startJSON']);
        $end = \DateTime::createFromFormat("Y-n-j G:i:s", $data['endJSON']);
        $sinceStart = $start->diff($end);
        $minutes = $sinceStart->days * 24 * 60;
        $minutes += $sinceStart->h * 60;
        $minutes += $sinceStart->i;

        $program->setProperties($data, $this->_em);
        $program->duration = $minutes / $basicBlockDuration;
        $this->_em->persist($program);
        $this->_em->flush();
        return $program;
    }
}
