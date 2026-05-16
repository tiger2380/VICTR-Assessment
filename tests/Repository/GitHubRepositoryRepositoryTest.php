<?php

namespace App\Tests\Repository;

use App\Entity\GitHubRepository;
use App\Repository\GitHubRepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GitHubRepositoryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private GitHubRepositoryRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->entityManager->beginTransaction();

        $this->repository = $this->entityManager->getRepository(GitHubRepository::class);
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        $this->entityManager->close();

        parent::tearDown();
    }

    private function makeRepo(int $id, string $name, int $stars): GitHubRepository
    {
        $repo = (new GitHubRepository())
            ->setGithubId($id)
            ->setName($name)
            ->setUrl("https://github.com/user/{$name}")
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPushedAt(new \DateTimeImmutable())
            ->setStarsCount($stars);

        $this->entityManager->persist($repo);

        return $repo;
    }

    public function testFindAllOrderedByStarsReturnsMostStarredFirst(): void
    {
        $this->makeRepo(101, 'low-stars', 10);
        $this->makeRepo(102, 'high-stars', 500);
        $this->makeRepo(103, 'mid-stars', 100);
        $this->entityManager->flush();

        $results = $this->repository->findAllOrderedByStars();

        $this->assertCount(3, $results);
        $this->assertSame(500, $results[0]->getStarsCount());
        $this->assertSame(100, $results[1]->getStarsCount());
        $this->assertSame(10, $results[2]->getStarsCount());
    }

    public function testFindAllOrderedByStarsReturnsEmptyArray(): void
    {
        $results = $this->repository->findAllOrderedByStars();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindAllOrderedByStarsReturnsAllPersistedRepositories(): void
    {
        $this->makeRepo(201, 'repo-a', 42);
        $this->makeRepo(202, 'repo-b', 99);
        $this->entityManager->flush();

        $results = $this->repository->findAllOrderedByStars();

        $names = array_map(fn(GitHubRepository $r) => $r->getName(), $results);
        $this->assertContains('repo-a', $names);
        $this->assertContains('repo-b', $names);
    }
}
