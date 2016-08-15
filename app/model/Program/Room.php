<?php

namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * Entita mistnosti
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\RoomRepository")
 * @JMS\ExclusionPolicy("none")
 * @property \Doctrine\Common\Collections\ArrayCollection $programs
 * @property string $name
 */
class Room extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\Program\Program", mappedBy="room", cascade={"persist"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<SRS\Model\Program\Program>")
     * @JMS\Exclude
     */
    protected $programs;

    /**
     * @ORM\Column
     *
     * @JMS\Type("string")
     */
    protected $name;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPrograms($programs)
    {
        $this->programs = $programs;
    }

    public function getPrograms()
    {
        return $this->programs;
    }

}

/**
 * Vlastni repozitar pro praci s místnostmi
 */
class RoomRepository extends \Nella\Doctrine\Repository
{

}
