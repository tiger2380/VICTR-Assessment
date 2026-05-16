<?php

namespace App\Tests\Controller;

use App\Entity\GitHubRepository;
use App\Repository\GitHubRepositoryRepository;
use App\Service\GitHubApiService;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class GitHubControllerTest extends WebTestCase
{
    private GitHubRepositoryRepository&Stub $repository;
    private GitHubApiService&Stub $apiService;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(GitHubRepositoryRepository::class);
        $this->apiService = $this->createStub(GitHubApiService::class);
    }

    private function makeRepo(): GitHubRepository
    {
        $repo = new GitHubRepository();
        $repo->setGithubId(1)
            ->setName('user/repo')
            ->setUrl('https://github.com/user/repo')
            ->setDescription('A repository')
            ->setStarsCount(1000)
            ->setCreatedAt(new \DateTimeImmutable('2020-01-01'))
            ->setPushedAt(new \DateTimeImmutable('2024-06-01'));

        return $repo;
    }

    public function testIndexRendersPage(): void
    {
        $client = static::createClient();

        $this->repository->method('findAllOrderedByStars')->willReturn([$this->makeRepo()]);
        static::getContainer()->set(GitHubRepositoryRepository::class, $this->repository);

        $client->request('GET', '/github');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    public function testIndexWithNoRepos(): void
    {
        $client = static::createClient();

        $this->repository->method('findAllOrderedByStars')->willReturn([]);
        static::getContainer()->set(GitHubRepositoryRepository::class, $this->repository);

        $client->request('GET', '/github');

        $this->assertResponseIsSuccessful();
    }

    public function testRefreshReturnsJson(): void
    {
        $client = static::createClient();
        $repo = $this->makeRepo();

        $this->apiService->method('refreshTopPhpRepositories')->willReturn(1);
        $this->repository->method('findAllOrderedByStars')->willReturn([$repo]);

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        static::getContainer()->set('security.csrf.token_manager', $csrfManager);
        static::getContainer()->set(GitHubApiService::class, $this->apiService);
        static::getContainer()->set(GitHubRepositoryRepository::class, $this->repository);

        $client->request('POST', '/github/refresh', ['_token' => 'test-token']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('Database refreshed', $data['message']);
        $this->assertSame(1, $data['count']);
        $this->assertCount(1, $data['repos']);
        $this->assertSame(1, $data['repos'][0]['githubId']);
        $this->assertSame('user/repo', $data['repos'][0]['name']);
        $this->assertSame('https://github.com/user/repo', $data['repos'][0]['url']);
        $this->assertSame('A repository', $data['repos'][0]['description']);
        $this->assertSame(1000, $data['repos'][0]['starsCount']);
        $this->assertSame('2020-01-01', $data['repos'][0]['createdAt']);
        $this->assertSame('2024-06-01', $data['repos'][0]['pushedAt']);
    }

    public function testRefreshCallsApiService(): void
    {
        $client = static::createClient();

        $apiServiceMock = $this->createMock(GitHubApiService::class);
        $apiServiceMock->expects($this->once())->method('refreshTopPhpRepositories')->willReturn(0);
        $this->repository->method('findAllOrderedByStars')->willReturn([]);

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        static::getContainer()->set('security.csrf.token_manager', $csrfManager);
        static::getContainer()->set(GitHubApiService::class, $apiServiceMock);
        static::getContainer()->set(GitHubRepositoryRepository::class, $this->repository);

        $client->request('POST', '/github/refresh', ['_token' => 'test-token']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['repos']);
    }

    public function testRefreshReturnsForbiddenWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/github/refresh', ['_token' => 'invalid-token']);

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid CSRF token', $data['error']);
    }

    public function testRefreshRequiresPostMethod(): void
    {
        $client = static::createClient();

        $client->request('GET', '/github/refresh');

        $this->assertResponseStatusCodeSame(405);
    }
}
