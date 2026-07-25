<?php

declare(strict_types=1);

namespace Polyscope\Laravel;

use GuzzleHttp\Client as HttpClient;

class Polyscope
{
    use Actions\ManagesModels;
    use Actions\ManagesRepositories;
    use Actions\ManagesServers;
    use Actions\ManagesTeam;
    use Actions\ManagesWorkspaceActions;
    use Actions\ManagesWorkspaceMessages;
    use Actions\ManagesWorkspaces;
    use MakesHttpRequests;

    public const DEFAULT_BASE_URL = 'https://getpolyscope.com';

    protected ?string $apiToken = null;

    protected string $baseUrl = self::DEFAULT_BASE_URL;

    public int $timeout = 30;

    public ?HttpClient $guzzle = null;

    protected bool $usesCustomClient = false;

    public function __construct(?string $apiToken = null, ?HttpClient $guzzle = null, ?string $baseUrl = null)
    {
        if ($baseUrl !== null) {
            $this->setBaseUrl($baseUrl);
        }

        $apiToken ??= $this->resolveFallbackApiToken();

        if ($apiToken !== null) {
            $this->setApiToken($apiToken, $guzzle);

            return;
        }

        if ($guzzle !== null) {
            $this->guzzle = $guzzle;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $collection
     * @param  class-string  $class
     * @param  array<string, mixed>  $extraData
     * @return array<int, object>
     */
    protected function transformCollection(array $collection, string $class, array $extraData = []): array
    {
        return array_map(function (array $attributes) use ($class, $extraData): object {
            return new $class($attributes + $extraData, $this);
        }, $collection);
    }

    public function setApiToken(string $apiToken, ?HttpClient $guzzle = null): static
    {
        $this->apiToken = $apiToken;
        $this->usesCustomClient = $guzzle !== null;

        $this->guzzle = $guzzle ?? new HttpClient([
            'base_uri' => rtrim($this->baseUrl, '/').'/api/',
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Polyscope Laravel SDK/0.1',
            ],
        ]);

        return $this;
    }

    public function setApiKey(string $apiToken, ?HttpClient $guzzle = null): static
    {
        return $this->setApiToken($apiToken, $guzzle);
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        if ($this->apiToken !== null && ! $this->usesCustomClient) {
            $this->setApiToken($this->apiToken);
        }

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    protected function resolveFallbackApiToken(): ?string
    {
        foreach ($this->candidateSettingsPaths() as $settingsPath) {
            if (! is_file($settingsPath) || ! is_readable($settingsPath)) {
                continue;
            }

            $settingsContents = file_get_contents($settingsPath);

            if (! is_string($settingsContents) || trim($settingsContents) === '') {
                continue;
            }

            $settings = json_decode($settingsContents, true);

            if (! is_array($settings)) {
                continue;
            }

            $token = $settings['authToken'] ?? null;

            if (is_string($token) && trim($token) !== '') {
                return trim($token);
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function candidateSettingsPaths(): array
    {
        $paths = [];

        $customSettingsPath = getenv('POLYSCOPE_SETTINGS_PATH');

        if (is_string($customSettingsPath) && trim($customSettingsPath) !== '') {
            $paths[] = trim($customSettingsPath);
        }

        $home = getenv('HOME');

        if (! is_string($home) || trim($home) === '') {
            $home = getenv('USERPROFILE');
        }

        if (is_string($home) && trim($home) !== '') {
            $home = rtrim($home, '/\\');

            $paths[] = $home.DIRECTORY_SEPARATOR.'.polyscope'.DIRECTORY_SEPARATOR.'settings.json';
            $paths[] = $home.DIRECTORY_SEPARATOR.'Library'.DIRECTORY_SEPARATOR.'Application Support'.DIRECTORY_SEPARATOR.'com.getpolyscope.app'.DIRECTORY_SEPARATOR.'settings.json';
            $paths[] = $home.DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'com.getpolyscope.app'.DIRECTORY_SEPARATOR.'settings.json';
        }

        $appData = getenv('APPDATA');

        if (is_string($appData) && trim($appData) !== '') {
            $paths[] = rtrim($appData, '/\\').DIRECTORY_SEPARATOR.'com.getpolyscope.app'.DIRECTORY_SEPARATOR.'settings.json';
        }

        return array_values(array_unique($paths));
    }
}
