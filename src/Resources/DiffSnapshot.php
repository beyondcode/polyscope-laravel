<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class DiffSnapshot extends Resource
{
    public ?string $diff = null;

    /** @var array<int, DiffFile> */
    public array $files = [];

    public DiffStats|array|null $stats = null;

    protected function fill(): void
    {
        parent::fill();

        $files = $this->attributes['files'] ?? [];
        $stats = $this->attributes['stats'] ?? null;

        $this->files = $this->transformCollection(
            is_array($files) ? $files : [],
            DiffFile::class,
        );

        $this->stats = is_array($stats)
            ? new DiffStats($stats, $this->polyscope)
            : null;
    }
}
