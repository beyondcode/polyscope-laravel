<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class AvailableAgent extends Resource
{
    public ?string $id = null;

    public ?string $name = null;

    /** @var array<int, string> */
    public array $models = [];
}
