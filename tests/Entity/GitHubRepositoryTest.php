<?php

namespace App\Tests\Entity;

use App\Entity\GitHubRepository;
use PHPUnit\Framework\TestCase;

class GitHubRepositoryTest extends TestCase
{
    private GitHubRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new GitHubRepository();
    }

    public function testGetSetGithubId(): void
    {
        $this->repo->setGithubId(12345);
        $this->assertSame(12345, $this->repo->getGithubId());
    }

    public function testGetSetName(): void
    {
        $this->repo->setName('my-repo');
        $this->assertSame('my-repo', $this->repo->getName());
    }

    public function testGetSetUrl(): void
    {
        $this->repo->setUrl('https://github.com/user/my-repo');
        $this->assertSame('https://github.com/user/my-repo', $this->repo->getUrl());
    }

    public function testGetSetCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-15 10:00:00');
        $this->repo->setCreatedAt($date);
        $this->assertSame($date, $this->repo->getCreatedAt());
    }

    public function testGetSetPushedAt(): void
    {
        $date = new \DateTimeImmutable('2024-06-01 12:30:00');
        $this->repo->setPushedAt($date);
        $this->assertSame($date, $this->repo->getPushedAt());
    }

    public function testGetSetDescriptionWithValue(): void
    {
        $this->repo->setDescription('A useful library');
        $this->assertSame('A useful library', $this->repo->getDescription());
    }

    public function testGetSetDescriptionWithNull(): void
    {
        $this->repo->setDescription(null);
        $this->assertNull($this->repo->getDescription());
    }

    public function testGetSetStarsCount(): void
    {
        $this->repo->setStarsCount(42);
        $this->assertSame(42, $this->repo->getStarsCount());
    }

    public function testSettersReturnStatic(): void
    {
        $date = new \DateTimeImmutable();
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setGithubId(1));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setName('repo'));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setUrl('https://github.com/user/repo'));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setCreatedAt($date));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setPushedAt($date));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setDescription('desc'));
        $this->assertInstanceOf(GitHubRepository::class, $this->repo->setStarsCount(0));
    }
}
