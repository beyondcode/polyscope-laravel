<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

use RuntimeException;

class MissingApiTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No API client configured. Call setApiToken() first.');
    }
}
