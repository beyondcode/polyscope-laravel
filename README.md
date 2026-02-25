# Polyscope Laravel SDK

A PHP SDK for the Polyscope API, built in a Laravel-friendly style.

## Installation

```bash
composer require beyondcode/polyscope
```

## Quick Start

```php
use Polyscope\Laravel\Polyscope;

$polyscope = new Polyscope('your-api-token');

$servers = $polyscope->servers();
$repositories = $polyscope->repositories();
$workspaces = $polyscope->workspaces();

// Create a workspace directly from a repository resource.
$workspace = $repositories[0]->createWorkspace([
    'prompt' => 'Fix the login flow',
    'server_id' => 'srv-1', // optional
]);

// Or use a prompt string shorthand.
$workspace = $repositories[0]->createWorkspace('Fix the login flow');
```

If no token is passed, the SDK automatically falls back to Polyscope's local settings file and reads `authToken`.
By default it checks:

- `~/.polyscope/settings.json`
- `~/Library/Application Support/com.getpolyscope.app/settings.json`
- `~/.config/com.getpolyscope.app/settings.json`
- `%APPDATA%/com.getpolyscope.app/settings.json`

You can override this with:

```bash
POLYSCOPE_SETTINGS_PATH=/custom/path/settings.json
```

If you need a custom base URL:

```php
$polyscope = (new Polyscope())
    ->setBaseUrl('https://your-polyscope-instance.com')
    ->setApiToken('your-api-token');
```

## Laravel Integration

The SDK auto-registers a service provider. Add credentials to `config/services.php`:

```php
'polyscope' => [
    'token' => env('POLYSCOPE_TOKEN'),
    'url' => env('POLYSCOPE_URL', 'https://getpolyscope.com'),
],
```

Then use the facade:

```php
use Polyscope\Laravel\Facades\Polyscope;

$workspace = Polyscope::createWorkspace([
    'repository_id' => 'repo-1',
    'prompt' => 'Fix the login flow and add tests',
]);
```

## Supported API Areas (v1 only)

- Servers: `servers()`
- Repositories: `repositories()`
- Workspaces: `workspaces()`, `createWorkspace()`, `workspace()`, `deleteWorkspace()`
- Workspace messages: `workspaceMessages()`, `sendWorkspaceMessage()`
- Workspace actions: `workspaceDiff()`, `commitWorkspace()`, `createWorkspacePullRequest()`, `stopWorkspace()`

## Error Handling

The SDK maps common HTTP/API failures to typed exceptions:

- `ValidationException`
- `AuthenticationException`
- `ForbiddenException`
- `NotFoundException`
- `RateLimitExceededException`
- `ServerOfflineException`
- `ServerTimeoutException`
- `ApiException`

Example:

```php
use Polyscope\Laravel\Exceptions\ValidationException;

try {
    $polyscope->createWorkspace(['prompt' => 'Missing repository_id']);
} catch (ValidationException $e) {
    $errors = $e->errors();
}
```

## Development

```bash
composer test
```
