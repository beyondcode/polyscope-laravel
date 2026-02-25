<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\GenericResource;
use Polyscope\Laravel\Resources\WorkspaceDiff;

trait ManagesWorkspaceActions
{
    public function workspaceDiff(string $workspaceId): WorkspaceDiff
    {
        $response = $this->get("v1/workspaces/{$workspaceId}/diff");

        return new WorkspaceDiff(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }

    public function commitWorkspace(string $workspaceId): GenericResource
    {
        $response = $this->post("v1/workspaces/{$workspaceId}/commit");

        return new GenericResource(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }

    public function createWorkspacePullRequest(
        string $workspaceId,
        ?string $title = null,
        ?string $body = null,
        bool $draft = false,
    ): GenericResource {
        $payload = array_filter([
            'title' => $title,
            'body' => $body,
            'draft' => $draft ? true : null,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->post("v1/workspaces/{$workspaceId}/pull-request", $payload);

        return new GenericResource(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }

    public function stopWorkspace(string $workspaceId): GenericResource
    {
        $response = $this->post("v1/workspaces/{$workspaceId}/stop");

        return new GenericResource(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }
}
