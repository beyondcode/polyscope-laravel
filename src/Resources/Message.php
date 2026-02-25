<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class Message extends Resource
{
    public ?int $id = null;

    public ?string $sessionId = null;

    public ?string $role = null;

    public ?string $content = null;

    public ?string $metadata = null;

    public ?string $createdAt = null;
}
