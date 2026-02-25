<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

use RuntimeException;

class Workspace extends Resource
{
    public ?string $id = null;

    public ?string $repoId = null;

    public ?string $serverId = null;

    public ?string $branch = null;

    public bool $branchRenamed = false;

    public ?string $path = null;

    public ?string $previewUrl = null;

    public ?string $status = null;

    public ?int $prNumber = null;

    public ?string $prUrl = null;

    public ?string $prTitle = null;

    public ?string $prBody = null;

    public bool $prIsDraft = false;

    public ?int $issueNumber = null;

    public ?string $issueTitle = null;

    public ?string $issueUrl = null;

    public ?string $baseBranch = null;

    public ?string $baseCommit = null;

    /** @var array<int, string> */
    public array $linkedWorktreeIds = [];

    public ?string $createdAt = null;

    /** @var array{id?: string, name?: string, full_name?: string, path?: string}|null */
    public ?array $repository = null;

    /** @var array{status?: string}|null */
    public ?array $agent = null;

    /** @var array<string, mixed>|null */
    public ?array $stats = null;

    public function refresh(): self
    {
        return $this->client()->workspace($this->id());
    }

    public function delete(): bool
    {
        return $this->client()->deleteWorkspace($this->id());
    }

    public function messages(?int $after = null): WorkspaceMessages
    {
        return $this->client()->workspaceMessages($this->id(), $after);
    }

    public function prompt(string $content, ?string $model = null): GenericResource
    {
        return $this->client()->sendWorkspaceMessage($this->id(), $content, $model);
    }

    public function diff(): WorkspaceDiff
    {
        return $this->client()->workspaceDiff($this->id());
    }

    public function commit(): GenericResource
    {
        return $this->client()->commitWorkspace($this->id());
    }

    public function pullRequest(?string $title = null, ?string $body = null, bool $draft = false): GenericResource
    {
        return $this->client()->createWorkspacePullRequest($this->id(), $title, $body, $draft);
    }

    public function stop(): GenericResource
    {
        return $this->client()->stopWorkspace($this->id());
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
            throw new RuntimeException('Workspace ID is missing from resource payload.');
        }

        return $this->id;
    }
}
