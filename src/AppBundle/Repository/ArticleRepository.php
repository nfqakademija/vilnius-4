<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ArticleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArticleRepository extends EntityRepository
{
    /**
     * Gets articles ordered by publish date.
     *
     * @return array
     */
    public function findAllByDate(): array
    {
        return $this->findBy([], ['publishDate' => 'DESC']);
    }
}
