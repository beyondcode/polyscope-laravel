<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class GenericResource extends Resource
{
    public ?string $workspaceId = null;

    /** @var array{status?: string}|null */
    public ?array $agent = null;
}
