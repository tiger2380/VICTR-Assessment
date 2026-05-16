<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class GitHubRepositoryData
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $id,

        #[Assert\NotBlank]
        public readonly string $fullName,

        #[Assert\NotBlank]
        #[Assert\Url]
        public readonly string $htmlUrl,

        public readonly ?string $description,

        #[Assert\PositiveOrZero]
        public readonly int $starsCount,

        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
        public readonly string $createdAt,

        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
        public readonly string $pushedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            fullName: (string) ($data['full_name'] ?? ''),
            htmlUrl: (string) ($data['html_url'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            starsCount: (int) ($data['stargazers_count'] ?? 0),
            createdAt: (string) ($data['created_at'] ?? ''),
            pushedAt: (string) ($data['pushed_at'] ?? ''),
        );
    }
}
