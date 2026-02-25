<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\GenericResource;
use Polyscope\Laravel\Resources\WorkspaceMessages;

trait ManagesWorkspaceMessages
{
    public function workspaceMessages(string $workspaceId, ?int $after = null): WorkspaceMessages
    {
        $query = array_filter([
            'after' => $after,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->get("v1/workspaces/{$workspaceId}/messages", $query);

        $attributes = [
            'messages' => is_array($response) ? ($response['data'] ?? []) : [],
            'cursor' => is_array($response) && isset($response['meta']) && is_array($response['meta'])
                ? ($response['meta']['cursor'] ?? null)
                : null,
            'has_more' => is_array($response) && isset($response['meta']) && is_array($response['meta'])
                ? ($response['meta']['has_more'] ?? false)
                : false,
        ];

        return new WorkspaceMessages($attributes, $this);
    }

    public function sendWorkspaceMessage(string $workspaceId, string $content, ?string $model = null): GenericResource
    {
        $payload = array_filter([
            'content' => $content,
            'model' => $model,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->post("v1/workspaces/{$workspaceId}/messages", $payload);

        return new GenericResource(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }
}
