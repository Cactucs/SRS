<?php

namespace App\Model\Program;

use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;

class BlockRepository extends EntityRepository
{
    /** @var UserRepository */
    private $userRepository;

    /**
     * @param UserRepository $userRepository
     */
    public function injectUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param $id
     * @return Block|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return int
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('b')
            ->select('MAX(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function findAllNames() {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @return array
     */
    public function findAllOrderedByName() {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllUncategorizedOrderedByName() {
        return $this->createQueryBuilder('b')
            ->where('b.category IS NULL')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $text
     * @param bool $unassignedOnly
     * @return array
     */
    public function findByLikeNameOrderedByName($text, $unassignedOnly = false) {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.name LIKE :text')->setParameter('text', '%' . $text . '%');

        if ($unassignedOnly) {
            $qb = $qb->leftJoin('b.programs', 'p')
                ->andWhere('SIZE(b.programs) = 0');
        }

        return $qb->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @return array
     */
    public function findUserMandatoryNotRegisteredNames($user)
    {
        $registerableCategoriesIds = $this->userRepository->findRegisterableCategoriesIdsByUser($user);

        $usersBlocks = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.programs', 'p')
            ->leftJoin('p.attendees', 'u')
            ->where('u.id = :uid')
            ->setParameter('uid', $user->getId())
            ->getQuery()
            ->getResult();

        $qb = $this->createQueryBuilder('b')
            ->select('b.name')
            ->leftJoin('b.category', 'c')
            ->where($this->createQueryBuilder()->expr()->orX(
                'c.id IN (:ids)',
                'b.category IS NULL'
            ))
            ->andWhere('b.mandatory > 0')
            ->setParameter('ids', $registerableCategoriesIds);

        if (!empty($usersBlocks)) {
            $qb = $qb
                ->andWhere('b NOT IN (:usersBlocks)')
                ->setParameter('usersBlocks', $usersBlocks);
        }

        $names = $qb
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * @param $blocks
     * @return array
     */
    public function findBlocksIds($blocks)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $blocks->toArray());
    }

    /**
     * @param Block $block
     */
    public function save(Block $block)
    {
        $this->_em->persist($block);
        $this->_em->flush();
    }

    /**
     * @param Block $block
     */
    public function remove(Block $block)
    {
        foreach ($block->getPrograms() as $program)
            $this->_em->remove($program);

        $this->_em->remove($block);
        $this->_em->flush();
    }
}