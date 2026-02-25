<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $errorCode = null,
        public readonly mixed $responseBody = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
