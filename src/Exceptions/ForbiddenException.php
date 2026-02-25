<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Forbidden.', mixed $responseBody = null)
    {
        parent::__construct($message, 403, null, $responseBody);
    }
}
