<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\ServerModels;

trait ManagesModels
{
    /**
     * @return array<int, ServerModels>
     */
    public function models(?string $serverId = null): array
    {
        $query = array_filter([
            'server_id' => $serverId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $response = $this->get('v1/models', $query);

        return $this->transformCollection(
            is_array($response) ? ($response['data'] ?? []) : [],
            ServerModels::class,
        );
    }
}
