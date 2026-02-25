<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class NotFoundException extends ApiException
{
    public function __construct(string $message = 'The requested resource was not found.', mixed $responseBody = null)
    {
        parent::__construct($message, 404, null, $responseBody);
    }
}
