<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\Workspace;

trait ManagesWorkspaces
{
    /**
     * @param  array{server_id?: string, repository_id?: string, status?: string}  $filters
     * @return array<int, Workspace>
     */
    public function workspaces(array $filters = []): array
    {
        $query = array_filter([
            'server_id' => $filters['server_id'] ?? null,
            'repository_id' => $filters['repository_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $response = $this->get('v1/workspaces', $query);

        return $this->transformCollection(
            is_array($response) ? ($response['data'] ?? []) : [],
            Workspace::class,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWorkspace(array $data): Workspace
    {
        $payload = array_filter($data, static fn (mixed $value): bool => $value !== null);
        $response = $this->post('v1/workspaces', $payload);

        return new Workspace(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }

    public function createWorkspaceFromIssue(string $repositoryId, string $issueUrl, ?string $serverId = null): Workspace
    {
        return $this->createWorkspace([
            'repository_id' => $repositoryId,
            'issue_url' => $issueUrl,
            'server_id' => $serverId,
        ]);
    }

    public function createWorkspaceFromLinearIssue(string $repositoryId, string $linearIssueUrl, ?string $serverId = null): Workspace
    {
        return $this->createWorkspace([
            'repository_id' => $repositoryId,
            'linear_issue_url' => $linearIssueUrl,
            'server_id' => $serverId,
        ]);
    }

    public function createWorkspaceFromPullRequest(string $repositoryId, string $pullRequestUrl, ?string $serverId = null): Workspace
    {
        return $this->createWorkspace([
            'repository_id' => $repositoryId,
            'pull_request_url' => $pullRequestUrl,
            'server_id' => $serverId,
        ]);
    }

    public function workspace(string $id): Workspace
    {
        $response = $this->get("v1/workspaces/{$id}");

        return new Workspace(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }

    public function deleteWorkspace(string $id): bool
    {
        $response = $this->delete("v1/workspaces/{$id}");

        if (! is_array($response) || ! isset($response['data']) || ! is_array($response['data'])) {
            return false;
        }

        return (bool) ($response['data']['deleted'] ?? false);
    }
}
