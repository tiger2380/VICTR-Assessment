<?php

namespace App\Entity;

use App\Repository\GitHubRepositoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GitHubRepositoryRepository::class)]
#[ORM\Table(name: 'github_repository')]
class GitHubRepository
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $githubId;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 512)]
    private string $url;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $pushedAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: Types::INTEGER)]
    private int $starsCount;

    public function getGithubId(): int
    {
        return $this->githubId;
    }

    public function setGithubId(int $githubId): static
    {
        $this->githubId = $githubId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPushedAt(): \DateTimeImmutable
    {
        return $this->pushedAt;
    }

    public function setPushedAt(\DateTimeImmutable $pushedAt): static
    {
        $this->pushedAt = $pushedAt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStarsCount(): int
    {
        return $this->starsCount;
    }

    public function setStarsCount(int $starsCount): static
    {
        $this->starsCount = $starsCount;

        return $this;
    }
}
