<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\Server;

trait ManagesServers
{
    /**
     * @return array<int, Server>
     */
    public function servers(): array
    {
        $response = $this->get('v1/servers');

        return $this->transformCollection(
            is_array($response) ? ($response['data'] ?? []) : [],
            Server::class,
        );
    }
}
