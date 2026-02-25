<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class DiffFile extends Resource
{
    public ?string $path = null;

    public int $additions = 0;

    public int $deletions = 0;
}
