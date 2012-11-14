<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:08
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;
use \NiftyGrid\Grid;

/**
 * Grid pro správu uživatelů a práv
 */
class UserGrid extends Grid
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManager
     */
    public function __construct($em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure($presenter)
    {
        $source = new \NiftyGrid\DataSource\DoctrineDataSource($this->em->createQueryBuilder()->add('select', 'u')->add('from', '\SRS\Model\User u'), 'u_id');
        $this->setDataSource($source);
        $this->addColumn('u_username', 'Username')->setTextFilter();
        $this->addColumn('u_nickName', 'Přezdívka')->setTextFilter();
        $this->addColumn('u_firstName', 'Jméno')->setTextFilter();
        $this->addColumn('u_lastName', 'Příjmení')->setTextFilter();

    }

}
