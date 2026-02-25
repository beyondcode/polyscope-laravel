<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class WorkspaceDiff extends DiffSnapshot
{
    public DiffSnapshot|array|null $local = null;

    public DiffSnapshot|array|null $base = null;

    public int $commitsAhead = 0;

    protected function fill(): void
    {
        parent::fill();

        $local = $this->attributes['local'] ?? null;
        $base = $this->attributes['base'] ?? null;

        $this->local = is_array($local)
            ? new DiffSnapshot($local, $this->polyscope)
            : null;

        $this->base = is_array($base)
            ? new DiffSnapshot($base, $this->polyscope)
            : null;

        $this->commitsAhead = (int) ($this->attributes['commitsAhead'] ?? 0);
    }
}
