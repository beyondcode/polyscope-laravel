<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class RateLimitExceededException extends ApiException
{
    public function __construct(
        string $message = 'Too Many Requests.',
        mixed $responseBody = null,
        public readonly ?int $rateLimitResetsAt = null,
    ) {
        parent::__construct($message, 429, null, $responseBody);
    }
}
