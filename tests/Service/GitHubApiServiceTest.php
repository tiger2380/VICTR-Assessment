<?php

namespace App\Tests\Service;

use App\Entity\GitHubRepository;
use App\Service\GitHubApiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GitHubApiServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    private function makeService(string $token = ''): GitHubApiService
    {
        return new GitHubApiService($this->httpClient, $this->entityManager, $token);
    }

    private function makeRepoData(int $id = 1): array
    {
        return [
            'id' => $id,
            'full_name' => 'user/repo-' . $id,
            'html_url' => 'https://github.com/user/repo-' . $id,
            'description' => 'A test repository',
            'stargazers_count' => 1000 * $id,
            'created_at' => '2020-01-01T00:00:00Z',
            'pushed_at' => '2024-06-01T12:00:00Z',
        ];
    }

    private function mockHttpResponse(array $items): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => $items]);

        $this->httpClient->method('request')->willReturn($response);
    }

    public function testRefreshTopPhpRepositoriesReturnsCount(): void
    {
        $items = [$this->makeRepoData(1), $this->makeRepoData(2)];
        $this->mockHttpResponse($items);

        $this->entityManager->method('find')->willReturn(null);
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $count = $this->makeService()->refreshTopPhpRepositories();

        $this->assertSame(2, $count);
    }

    public function testRefreshCreatesNewRepositoriesWhenNotFound(): void
    {
        $this->mockHttpResponse([$this->makeRepoData(42)]);

        $this->entityManager->method('find')->with(GitHubRepository::class, 42)->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(function (GitHubRepository $repo) {
                return $repo->getGithubId() === 42
                    && $repo->getName() === 'user/repo-42'
                    && $repo->getUrl() === 'https://github.com/user/repo-42'
                    && $repo->getDescription() === 'A test repository'
                    && $repo->getStarsCount() === 42000;
            }));
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }

    public function testRefreshUpdatesExistingRepositories(): void
    {
        $existing = new GitHubRepository();
        $existing->setGithubId(7);
        $existing->setName('old/name');
        $existing->setUrl('https://github.com/old/name');
        $existing->setStarsCount(10);
        $existing->setCreatedAt(new \DateTimeImmutable('2019-01-01'));
        $existing->setPushedAt(new \DateTimeImmutable('2019-01-01'));

        $this->mockHttpResponse([$this->makeRepoData(7)]);

        $this->entityManager->method('find')->with(GitHubRepository::class, 7)->willReturn($existing);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService()->refreshTopPhpRepositories();

        $this->assertSame('user/repo-7', $existing->getName());
        $this->assertSame(7000, $existing->getStarsCount());
    }

    public function testRefreshHandlesEmptyItemsGracefully(): void
    {
        $this->mockHttpResponse([]);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $count = $this->makeService()->refreshTopPhpRepositories();

        $this->assertSame(0, $count);
    }

    public function testRefreshHandlesNullDescription(): void
    {
        $data = $this->makeRepoData(3);
        unset($data['description']);
        $this->mockHttpResponse([$data]);

        $this->entityManager->method('find')->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(fn(GitHubRepository $repo) => $repo->getDescription() === null));
        $this->entityManager->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }

    public function testRequestSendsAuthorizationHeaderWhenTokenProvided(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/search/repositories',
                $this->callback(function (array $options) {
                    return isset($options['headers']['Authorization'])
                        && $options['headers']['Authorization'] === 'Bearer secret-token';
                })
            )
            ->willReturn($response);

        $this->entityManager->method('flush');

        $this->makeService('secret-token')->refreshTopPhpRepositories();
    }

    public function testRequestOmitsAuthorizationHeaderWhenNoTokenProvided(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/search/repositories',
                $this->callback(function (array $options) {
                    return !isset($options['headers']['Authorization']);
                })
            )
            ->willReturn($response);

        $this->entityManager->method('flush');

        $this->makeService('')->refreshTopPhpRepositories();
    }

    public function testRequestSendsCorrectQueryParameters(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/search/repositories',
                $this->callback(function (array $options) {
                    $q = $options['query'] ?? [];
                    return ($q['q'] ?? '') === 'language:php'
                        && ($q['sort'] ?? '') === 'stars'
                        && ($q['order'] ?? '') === 'desc'
                        && ($q['per_page'] ?? 0) === 30
                        && ($q['page'] ?? 0) === 1;
                })
            )
            ->willReturn($response);

        $this->entityManager->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }
}
