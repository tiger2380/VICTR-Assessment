<?php

namespace App\Service;

use App\DTO\GitHubRepositoryData;
use App\Entity\GitHubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitHubApiService
{
    private const SEARCH_URL = 'https://api.github.com/search/repositories';
    private const RESULTS_PER_PAGE = 30;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly string $githubToken = '',
    ) {
    }

    /**
     * Fetch most-starred public PHP repositories from GitHub and persist them.
     *
     * @return int Number of repositories upserted
     */
    public function refreshTopPhpRepositories(): int
    {
        $repos = $this->fetchFromGitHub();

        foreach ($repos as $data) {
            $dto = GitHubRepositoryData::fromArray($data);
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                throw new ValidationFailedException($dto, $violations);
            }

            $this->upsert($dto);
        }

        $this->entityManager->flush();

        return count($repos);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromGitHub(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'flagship-template-app',
        ];

        if ($this->githubToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->githubToken;
        }

        $response = $this->httpClient->request('GET', self::SEARCH_URL, [
            'headers' => $headers,
            'query' => [
                'q' => 'language:php',
                'sort' => 'stars',
                'order' => 'desc',
                'per_page' => self::RESULTS_PER_PAGE,
                'page' => 1,
            ],
        ]);

        $body = $response->toArray();

        return $body['items'] ?? [];
    }

    private function upsert(GitHubRepositoryData $data): void
    {
        $repo = $this->entityManager->find(GitHubRepository::class, $data->id);

        if ($repo === null) {
            $repo = new GitHubRepository();
            $repo->setGithubId($data->id);
            $this->entityManager->persist($repo);
        }

        $repo->setName($data->fullName);
        $repo->setUrl($data->htmlUrl);
        $repo->setDescription($data->description);
        $repo->setStarsCount($data->starsCount);
        $repo->setCreatedAt(new \DateTimeImmutable($data->createdAt));
        $repo->setPushedAt(new \DateTimeImmutable($data->pushedAt));
    }
}
