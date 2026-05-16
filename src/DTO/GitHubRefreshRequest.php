<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

final class GitHubRefreshRequest
{
    public function __construct(
        public readonly string $csrfToken,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            csrfToken: (string) $request->request->get('_token', ''),
        );
    }
}
