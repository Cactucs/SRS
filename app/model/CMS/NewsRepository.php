<?php

namespace App\Model\CMS;


use Kdyby\Doctrine\EntityRepository;

class NewsRepository extends EntityRepository
{
    /**
     * @param $id
     * @return News|null
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
        return $this->createQueryBuilder('n')
            ->select('MAX(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $maxCount
     * @return News[]
     */
    public function findPublishedOrderedByDate($maxCount)
    {
        return $this->createQueryBuilder('n')
            ->where($this->createQueryBuilder()->expr()->lte('n.published', 'CURRENT_TIMESTAMP()'))
            ->orderBy('n.published', 'DESC')
            ->setMaxResults($maxCount)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param News $news
     */
    public function save(News $news)
    {
        $this->_em->persist($news);
        $this->_em->flush();
    }

    /**
     * @param News $document
     */
    public function remove(News $document)
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}