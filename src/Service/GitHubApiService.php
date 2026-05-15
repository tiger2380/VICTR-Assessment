<?php

namespace App\Service;

use App\Entity\GitHubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitHubApiService
{
    private const SEARCH_URL = 'https://api.github.com/search/repositories';
    private const RESULTS_PER_PAGE = 30;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
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
            $this->upsert($data);
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

    /**
     * @param array<string, mixed> $data
     */
    private function upsert(array $data): void
    {
        $repo = $this->entityManager->find(GitHubRepository::class, (int) $data['id']);

        if ($repo === null) {
            $repo = new GitHubRepository();
            $repo->setGithubId((int) $data['id']);
            $this->entityManager->persist($repo);
        }

        $repo->setName((string) $data['full_name']);
        $repo->setUrl((string) $data['html_url']);
        $repo->setDescription(isset($data['description']) ? (string) $data['description'] : null);
        $repo->setStarsCount((int) $data['stargazers_count']);
        $repo->setCreatedAt(new \DateTimeImmutable((string) $data['created_at']));
        $repo->setPushedAt(new \DateTimeImmutable((string) $data['pushed_at']));
    }
}
