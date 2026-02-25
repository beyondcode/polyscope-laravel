<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Polyscope\Laravel\PolyscopeManager;

/**
 * @method static \Polyscope\Laravel\Polyscope client()
 * @method static \Polyscope\Laravel\Polyscope setApiToken(string $apiToken, ?\GuzzleHttp\Client $guzzle = null)
 * @method static \Polyscope\Laravel\Polyscope setApiKey(string $apiToken, ?\GuzzleHttp\Client $guzzle = null)
 * @method static string|null getApiToken()
 * @method static \Polyscope\Laravel\Polyscope setBaseUrl(string $baseUrl)
 * @method static array servers()
 * @method static array repositories(?string $serverId = null)
 * @method static array workspaces(array $filters = [])
 * @method static \Polyscope\Laravel\Resources\Workspace createWorkspace(array $data)
 * @method static \Polyscope\Laravel\Resources\Workspace workspace(string $id)
 * @method static bool deleteWorkspace(string $id)
 */
class Polyscope extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PolyscopeManager::class;
    }
}
