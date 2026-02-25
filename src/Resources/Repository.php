<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

use RuntimeException;

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

    /**
     * @param  array<string, mixed>|string  $data
     */
    public function createWorkspace(array|string $data = []): Workspace
    {
        if (is_string($data)) {
            $data = ['prompt' => $data];
        }

        $repositoryId = $this->id();

        if (
            isset($data['repository_id'])
            && is_string($data['repository_id'])
            && $data['repository_id'] !== $repositoryId
        ) {
            throw new RuntimeException('The provided repository_id does not match this repository resource.');
        }

        $data['repository_id'] = $repositoryId;

        return $this->client()->createWorkspace($data);
    }

    protected function client(): \Polyscope\Laravel\Polyscope
    {
        if ($this->polyscope === null) {
            throw new RuntimeException('No Polyscope client instance available for this resource.');
        }

        return $this->polyscope;
    }

    protected function id(): string
    {
        if ($this->id === null || $this->id === '') {
            throw new RuntimeException('Repository ID is missing from resource payload.');
        }

        return $this->id;
    }
}
