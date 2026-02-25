<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class Server extends Resource
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $version = null;

    public ?string $platform = null;

    public bool $online = false;

    public ?string $connectedAt = null;
}
