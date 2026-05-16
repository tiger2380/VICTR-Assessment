<?php

namespace App\Controller;

use App\Entity\GitHubRepository;
use App\Repository\GitHubRepositoryRepository;
use App\Service\GitHubApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/github', name: 'github_')]
class GitHubController extends AbstractController
{
    public function __construct(
        private readonly GitHubRepositoryRepository $repository,
        private readonly GitHubApiService $apiService,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $repos = $this->repository->findAllOrderedByStars();

        return $this->render('github/index.html.twig', [
            'repos' => $repos,
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        $count = $this->apiService->refreshTopPhpRepositories();

        $repos = $this->repository->findAllOrderedByStars();

        return new JsonResponse([
            'message' => 'Database refreshed',
            'count' => $count,
            'repos' => array_map(static fn (GitHubRepository $r) => [
                'githubId' => $r->getGithubId(),
                'name' => $r->getName(),
                'url' => $r->getUrl(),
                'description' => $r->getDescription(),
                'createdAt' => $r->getCreatedAt()->format('Y-m-d'),
                'pushedAt' => $r->getPushedAt()->format('Y-m-d'),
                'starsCount' => $r->getStarsCount(),
            ], $repos),
        ]);
    }
}
