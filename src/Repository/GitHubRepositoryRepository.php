<?php

namespace App\Repository;

use App\Entity\GitHubRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GitHubRepository>
 */
class GitHubRepositoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GitHubRepository::class);
    }

    /**
     * @return GitHubRepository[]
     */
    public function findAllOrderedByStars(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.starsCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
