<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Exceptions;

class ValidationException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'The given data failed to pass validation.',
    ) {
        parent::__construct($message, 422, null, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
