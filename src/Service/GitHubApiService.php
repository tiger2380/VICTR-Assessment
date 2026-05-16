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
     * Fetch most-starred public PHP repositories from GitHub, clear existing data, and persist the new ones.
     *
     * @return int Number of repositories inserted
     */
    public function refreshTopPhpRepositories(): int
    {
        $repos = $this->fetchFromGitHub();

        $this->entityManager->createQuery('DELETE FROM App\Entity\GitHubRepository r')->execute();

        foreach ($repos as $data) {
            $dto = GitHubRepositoryData::fromArray($data);
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                throw new ValidationFailedException($dto, $violations);
            }

            $this->insert($dto);
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

    private function insert(GitHubRepositoryData $data): void
    {
        $repo = new GitHubRepository();
        $repo->setGithubId($data->id);
        $repo->setName($data->fullName);
        $repo->setUrl($data->htmlUrl);
        $repo->setDescription($data->description);
        $repo->setStarsCount($data->starsCount);
        $repo->setCreatedAt(new \DateTimeImmutable($data->createdAt));
        $repo->setPushedAt(new \DateTimeImmutable($data->pushedAt));
        $this->entityManager->persist($repo);
    }
}
