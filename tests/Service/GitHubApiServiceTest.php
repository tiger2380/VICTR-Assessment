<?php

namespace App\Tests\Service;

use App\Entity\GitHubRepository;
use App\Service\GitHubApiService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GitHubApiServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    private function makeService(string $token = ''): GitHubApiService
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return new GitHubApiService($this->httpClient, $this->entityManager, $validator, $token);
    }

    private function mockDeleteQuery(): void
    {
        $query = $this->createStub(Query::class);
        $query->method('execute')->willReturn(null);
        $this->entityManager->method('createQuery')->willReturn($query);
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
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => $items]);

        $this->httpClient->method('request')->willReturn($response);
    }

    public function testRefreshTopPhpRepositoriesReturnsCount(): void
    {
        $items = [$this->makeRepoData(1), $this->makeRepoData(2)];
        $this->mockHttpResponse($items);
        $this->mockDeleteQuery();

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $count = $this->makeService()->refreshTopPhpRepositories();

        $this->assertSame(2, $count);
    }

    public function testRefreshDeletesExistingDataBeforeInserting(): void
    {
        $this->mockHttpResponse([$this->makeRepoData(1)]);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute');
        $this->entityManager->expects($this->once())->method('createQuery')
            ->with('DELETE FROM App\Entity\GitHubRepository r')
            ->willReturn($query);

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }

    public function testRefreshCreatesNewRepositories(): void
    {
        $this->mockHttpResponse([$this->makeRepoData(42)]);
        $this->mockDeleteQuery();

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

    public function testRefreshHandlesEmptyItemsGracefully(): void
    {
        $this->mockHttpResponse([]);
        $this->mockDeleteQuery();

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
        $this->mockDeleteQuery();

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(fn(GitHubRepository $repo) => $repo->getDescription() === null));
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }

    public function testRequestSendsAuthorizationHeaderWhenTokenProvided(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient = $this->createMock(HttpClientInterface::class);
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

        $this->mockDeleteQuery();
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService('secret-token')->refreshTopPhpRepositories();
    }

    public function testRequestOmitsAuthorizationHeaderWhenNoTokenProvided(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient = $this->createMock(HttpClientInterface::class);
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

        $this->mockDeleteQuery();
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService('')->refreshTopPhpRepositories();
    }

    public function testRequestSendsCorrectQueryParameters(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient = $this->createMock(HttpClientInterface::class);
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

        $this->mockDeleteQuery();
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService()->refreshTopPhpRepositories();
    }
}
