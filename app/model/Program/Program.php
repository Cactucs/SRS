<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 */
class Program
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs", cascade={"persist"})
     * @var Block
     */
    protected $block;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $attendees;

    /**
     * @ORM\ManyToOne(targetEntity="Room", cascade={"persist"})
     * @var Room
     */
    protected $room;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $duration;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $mandatory = false;

    /**
     * Program constructor.
     */
    public function __construct()
    {
        $this->attendees = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Block
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param Block $block
     */
    public function setBlock($block)
    {
        $this->block = $block;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * @param ArrayCollection $attendees
     */
    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    /**
     * @return Room
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param Room $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
    }

    /**
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param \DateTime $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    public function getEnd($basicBlockDuration) {
        return (clone $this->start)->modify('+' . $this->duration * $basicBlockDuration . ' minutes');
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }


}