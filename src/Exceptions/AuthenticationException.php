<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Unauthorized.', mixed $responseBody = null)
    {
        parent::__construct($message, 401, null, $responseBody);
    }
}
