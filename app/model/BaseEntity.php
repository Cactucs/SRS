<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 3.12.12
 * Time: 20:58
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Model;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * @property-read int $id
 */
abstract class BaseEntity extends \Nette\Object
{
    /**
     * @JMS\Type("integer")
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     *
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Slouzi pro vlozeni atributu z Nette Formulare
     * @param array $values
     * @param \Doctrine\ORM\EntityManager $em
     * @throws \Exception pak je nejspise chyba v metode a je treba ji opravit
     */
    public function setProperties($values = array(), $em)
    {
        $associtaionPosibilities = array ('ORM\ManyToMany', 'ORM\ManyToOne', 'ORM\OneToMany');
        $reflection = new \Nette\Reflection\ClassType($this);

        foreach($values as $key => $value){
               //pokud vubec existuje property s timto jmenem
               if ($reflection->hasProperty($key)) {
                   $propertyReflection = $reflection->getProperty($key);

                    //obsluhujeme vazby
                    if ($propertyReflection->hasAnnotation('ORM\ManyToMany') ||
                        $propertyReflection->hasAnnotation('ORM\ManyToOne') ||
                        $propertyReflection->hasAnnotation('ORM\OneToMany'))
                    {

                        $association = null;

                        foreach ($associtaionPosibilities as $possibility) {
                            $association = $propertyReflection->getAnnotation($possibility);
                            $targetEntity = $association['targetEntity'];
                            if ($association != null) break;
                        }
                        if ($association == null) {
                            throw new \Exception('Problem v prirazeni asociaci v BaseEntity->setProperties');
                        }

                        if (is_array($value)) { //vazba oneToMany nebo ManyToMany
                            $newData = new \Doctrine\Common\Collections\ArrayCollection();
                            foreach($value as $itemId) {
                                $newData->add($em->getReference($targetEntity, $itemId));
                            }
                            $value = $newData;
                        }
                        else { //vazba ManyToOne
                            $value = $em->getReference($targetEntity, $value);
                        }
                    }
                    //method_exists(get_class(),"set$key")
                    if ($key != 'id') {
                        $columnAnnotation = $propertyReflection->getAnnotation('ORM\Column');
                        if (isset($columnAnnotation['type']) && $columnAnnotation['type'] == 'date') {
                            $value = \DateTime::createFromFormat("Y-m-d", $value);
                        }
                        $this->{"set$key"}($value);
                    }
            }
        }

    }





}
