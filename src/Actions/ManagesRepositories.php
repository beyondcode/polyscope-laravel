<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\Repository;

trait ManagesRepositories
{
    /**
     * @return array<int, Repository>
     */
    public function repositories(?string $serverId = null): array
    {
        $query = array_filter([
            'server_id' => $serverId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $response = $this->get('v1/repositories', $query);

        return $this->transformCollection(
            is_array($response) ? ($response['data'] ?? []) : [],
            Repository::class,
        );
    }
}
