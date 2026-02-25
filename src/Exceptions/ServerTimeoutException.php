<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class ServerTimeoutException extends ApiException
{
    public function __construct(string $message = 'The server timed out.', mixed $responseBody = null)
    {
        parent::__construct($message, 504, 'server_timeout', $responseBody);
    }
}
