<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class ServerModels extends Resource
{
    public ?string $serverId = null;

    /** @var array<int, AvailableAgent> */
    public array $agents = [];

    /** @var array<int, string> */
    public array $models = [];

    /** @var array<int, array<string, mixed>> */
    public array $modelCapabilities = [];

    protected function fill(): void
    {
        parent::fill();

        $agents = $this->attributes['agents'] ?? [];

        $this->agents = $this->transformCollection(
            is_array($agents) ? $agents : [],
            AvailableAgent::class,
        );
    }
}
