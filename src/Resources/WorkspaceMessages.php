<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class WorkspaceMessages extends Resource
{
    /** @var array<int, Message> */
    public array $messages = [];

    public int|string|null $cursor = null;

    public bool $hasMore = false;

    protected function fill(): void
    {
        $messages = $this->attributes['messages'] ?? [];

        $this->messages = $this->transformCollection(
            is_array($messages) ? $messages : [],
            Message::class,
        );

        $this->cursor = $this->attributes['cursor'] ?? null;
        $this->hasMore = (bool) ($this->attributes['has_more'] ?? false);
    }
}
