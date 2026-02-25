<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class DiffStats extends Resource
{
    public int $filesChanged = 0;

    public int $additions = 0;

    public int $deletions = 0;
}
