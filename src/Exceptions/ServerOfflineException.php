<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class ServerOfflineException extends ApiException
{
    public function __construct(string $message = 'The server is offline.', mixed $responseBody = null)
    {
        parent::__construct($message, 503, 'server_offline', $responseBody);
    }
}
