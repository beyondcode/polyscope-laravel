<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

class Repository extends Resource
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $path = null;

    public ?string $baseBranch = null;

    public ?string $defaultModel = null;

    public bool $githubReactionEnabled = false;

    public ?string $githubReactionEmoji = null;

    public ?string $mergePrompt = null;

    public ?string $mergeAndPushPrompt = null;

    public ?string $prPrompt = null;

    public ?string $draftPrPrompt = null;

    public ?bool $worktreeBaseFromOrigin = null;

    public bool $hasRemote = false;

    /** @var array<int, string> */
    public array $linkedRepoIds = [];

    public ?string $createdAt = null;

    public ?string $serverId = null;
}
