<?php

namespace App\Controller;

use App\Entity\GitHubRepository;
use App\Repository\GitHubRepositoryRepository;
use App\Service\GitHubApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/{githubId}', name: 'show', requirements: ['githubId' => '\d+'])]
    public function show(int $githubId): Response
    {
        $repo = $this->repository->find($githubId);

        if ($repo === null) {
            throw $this->createNotFoundException('Repository not found.');
        }

        return $this->render('github/show.html.twig', [
            'repo' => $repo,
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): Response
    {
        $count = $this->apiService->refreshTopPhpRepositories();

        $this->addFlash('success', sprintf('Database refreshed — %d repositories loaded from GitHub.', $count));

        return $this->redirectToRoute('github_index');
    }
}
