<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Polyscope\Laravel\Exceptions\ServerOfflineException;
use Polyscope\Laravel\Exceptions\ValidationException;
use Polyscope\Laravel\Polyscope;
use Polyscope\Laravel\Resources\AvailableAgent;
use Polyscope\Laravel\Resources\DiffSnapshot;
use Polyscope\Laravel\Resources\GenericResource;
use Polyscope\Laravel\Resources\ServerModels;
use Polyscope\Laravel\Resources\Message;
use Polyscope\Laravel\Resources\Server;
use Polyscope\Laravel\Resources\Workspace;
use Polyscope\Laravel\Resources\WorkspaceDiff;
use Polyscope\Laravel\Resources\WorkspaceMessages;
use RuntimeException;

class PolyscopeSDKTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_falls_back_to_polyscope_settings_token_when_none_is_provided(): void
    {
        $settingsPath = tempnam(sys_get_temp_dir(), 'polyscope-settings-');
        file_put_contents($settingsPath, json_encode(['authToken' => 'fallback-token'], JSON_THROW_ON_ERROR));

        $previousPath = getenv('POLYSCOPE_SETTINGS_PATH');
        putenv("POLYSCOPE_SETTINGS_PATH={$settingsPath}");

        try {
            $sdk = new Polyscope();

            $this->assertSame('fallback-token', $sdk->getApiToken());
            $this->assertNotNull($sdk->guzzle);
        } finally {
            if ($previousPath === false) {
                putenv('POLYSCOPE_SETTINGS_PATH');
            } else {
                putenv("POLYSCOPE_SETTINGS_PATH={$previousPath}");
            }

            @unlink($settingsPath);
        }
    }

    public function test_explicit_token_wins_over_fallback_settings_token(): void
    {
        $settingsPath = tempnam(sys_get_temp_dir(), 'polyscope-settings-');
        file_put_contents($settingsPath, json_encode(['authToken' => 'fallback-token'], JSON_THROW_ON_ERROR));

        $previousPath = getenv('POLYSCOPE_SETTINGS_PATH');
        putenv("POLYSCOPE_SETTINGS_PATH={$settingsPath}");

        try {
            $sdk = new Polyscope('manual-token');

            $this->assertSame('manual-token', $sdk->getApiToken());
        } finally {
            if ($previousPath === false) {
                putenv('POLYSCOPE_SETTINGS_PATH');
            } else {
                putenv("POLYSCOPE_SETTINGS_PATH={$previousPath}");
            }

            @unlink($settingsPath);
        }
    }

    public function test_servers_are_transformed_to_resources(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/servers', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [[
                    'id' => 'srv-1',
                    'name' => 'MacBook Pro',
                    'version' => '1.0.0',
                    'platform' => 'darwin',
                    'online' => true,
                    'connected_at' => '2026-02-25T18:00:00+00:00',
                ]],
            ], JSON_THROW_ON_ERROR)));

        $servers = $sdk->servers();

        $this->assertCount(1, $servers);
        $this->assertInstanceOf(Server::class, $servers[0]);
        $this->assertSame('srv-1', $servers[0]->id);
        $this->assertSame('1.0.0', $servers[0]->version);
        $this->assertSame('darwin', $servers[0]->platform);
        $this->assertTrue($servers[0]->online);
        $this->assertSame('2026-02-25T18:00:00+00:00', $servers[0]->connectedAt);
    }

    public function test_repositories_support_server_filter(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/repositories', Mockery::on(function (array $options): bool {
                return ($options['timeout'] ?? null) === 30
                    && ($options['query']['server_id'] ?? null) === 'srv-1';
            }))
            ->andReturn(new Response(200, [], json_encode([
                'data' => [[
                    'id' => 'repo-1',
                    'name' => 'polyscope',
                    'path' => '/code/polyscope',
                    'base_branch' => 'main',
                    'default_model' => 'gpt-5.3-codex',
                    'github_reaction_enabled' => true,
                    'github_reaction_emoji' => 'eyes',
                    'merge_prompt' => null,
                    'merge_and_push_prompt' => 'Merge and push changes',
                    'pr_prompt' => 'Create pull request',
                    'draft_pr_prompt' => 'Create draft pull request',
                    'worktree_base_from_origin' => true,
                    'has_remote' => true,
                    'linked_repo_ids' => ['repo-2'],
                    'created_at' => '2026-02-25T18:00:00+00:00',
                    'server_id' => 'srv-1',
                ]],
            ], JSON_THROW_ON_ERROR)));

        $repositories = $sdk->repositories('srv-1');

        $this->assertCount(1, $repositories);
        $this->assertSame('repo-1', $repositories[0]->id);
        $this->assertSame('main', $repositories[0]->baseBranch);
        $this->assertSame('gpt-5.3-codex', $repositories[0]->defaultModel);
        $this->assertTrue($repositories[0]->githubReactionEnabled);
        $this->assertSame('eyes', $repositories[0]->githubReactionEmoji);
        $this->assertSame('Create pull request', $repositories[0]->prPrompt);
        $this->assertTrue($repositories[0]->worktreeBaseFromOrigin);
        $this->assertTrue($repositories[0]->hasRemote);
        $this->assertSame(['repo-2'], $repositories[0]->linkedRepoIds);
        $this->assertSame('srv-1', $repositories[0]->serverId);
    }

    public function test_models_are_transformed_to_server_models_resources(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/models', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [[
                    'server_id' => 'srv-1',
                    'agents' => [
                        [
                            'id' => 'claude',
                            'name' => 'Claude',
                            'models' => ['claude-opus-5', 'claude-sonnet-5'],
                        ],
                        [
                            'id' => 'codex',
                            'name' => 'Codex',
                            'models' => ['gpt-5.3-codex'],
                        ],
                    ],
                    'models' => ['claude-opus-5', 'claude-sonnet-5', 'gpt-5.3-codex'],
                    'model_capabilities' => [
                        [
                            'value' => 'claude-opus-5',
                            'displayName' => 'Opus 5',
                            'supportsFastMode' => true,
                        ],
                    ],
                ]],
            ], JSON_THROW_ON_ERROR)));

        $serverModels = $sdk->models();

        $this->assertCount(1, $serverModels);
        $this->assertInstanceOf(ServerModels::class, $serverModels[0]);
        $this->assertSame('srv-1', $serverModels[0]->serverId);
        $this->assertCount(2, $serverModels[0]->agents);
        $this->assertInstanceOf(AvailableAgent::class, $serverModels[0]->agents[0]);
        $this->assertSame('claude', $serverModels[0]->agents[0]->id);
        $this->assertSame('Claude', $serverModels[0]->agents[0]->name);
        $this->assertSame(['claude-opus-5', 'claude-sonnet-5'], $serverModels[0]->agents[0]->models);
        $this->assertSame(['claude-opus-5', 'claude-sonnet-5', 'gpt-5.3-codex'], $serverModels[0]->models);
        $this->assertSame('claude-opus-5', $serverModels[0]->modelCapabilities[0]['value']);
        $this->assertTrue($serverModels[0]->modelCapabilities[0]['supportsFastMode']);
    }

    public function test_models_support_server_filter(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/models', Mockery::on(function (array $options): bool {
                return ($options['timeout'] ?? null) === 30
                    && ($options['query']['server_id'] ?? null) === 'srv-1';
            }))
            ->andReturn(new Response(200, [], json_encode([
                'data' => [[
                    'server_id' => 'srv-1',
                    'agents' => [],
                    'models' => [],
                    'model_capabilities' => [],
                ]],
            ], JSON_THROW_ON_ERROR)));

        $serverModels = $sdk->models('srv-1');

        $this->assertCount(1, $serverModels);
        $this->assertSame('srv-1', $serverModels[0]->serverId);
        $this->assertSame([], $serverModels[0]->agents);
        $this->assertSame([], $serverModels[0]->modelCapabilities);
    }

    public function test_creating_workspace_from_linear_issue_sends_linear_issue_url(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces', [
                'timeout' => 30,
                'json' => [
                    'repository_id' => 'repo-1',
                    'linear_issue_url' => 'https://linear.app/team/issue/ABC-1',
                ],
            ])
            ->andReturn(new Response(201, [], json_encode([
                'data' => [
                    'id' => 'wt-1',
                    'repo_id' => 'repo-1',
                    'branch' => 'abc-1',
                    'status' => 'active',
                ],
            ], JSON_THROW_ON_ERROR)));

        $workspace = $sdk->createWorkspaceFromLinearIssue('repo-1', 'https://linear.app/team/issue/ABC-1');

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertSame('wt-1', $workspace->id);
    }

    public function test_inviting_team_member_returns_generic_resource(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/team/invites', [
                'timeout' => 30,
                'json' => [
                    'email' => 'teammate@example.com',
                ],
            ])
            ->andReturn(new Response(201, [], json_encode([
                'data' => [
                    'email' => 'teammate@example.com',
                    'invited' => true,
                ],
            ], JSON_THROW_ON_ERROR)));

        $result = $sdk->inviteTeamMember('teammate@example.com');

        $this->assertInstanceOf(GenericResource::class, $result);
        $this->assertSame('teammate@example.com', $result->email);
        $this->assertTrue($result->invited);
    }

    public function test_creating_workspace_returns_workspace_resource(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces', [
                'timeout' => 30,
                'json' => [
                    'repository_id' => 'repo-1',
                    'prompt' => 'Fix bug',
                ],
            ])
            ->andReturn(new Response(201, [], json_encode([
                'data' => [
                    'id' => 'wt-1',
                    'repo_id' => 'repo-1',
                    'server_id' => 'srv-1',
                    'branch' => 'fix-bug',
                    'branch_renamed' => false,
                    'path' => '/code/polyscope/.polyscope/worktrees/fix-bug',
                    'preview_url' => null,
                    'status' => 'active',
                    'pr_number' => null,
                    'pr_url' => null,
                    'pr_title' => null,
                    'pr_body' => null,
                    'pr_is_draft' => false,
                    'issue_number' => null,
                    'issue_title' => null,
                    'issue_url' => null,
                    'base_branch' => 'main',
                    'base_commit' => 'abc123',
                    'linked_worktree_ids' => [],
                    'created_at' => '2026-02-25T18:00:00+00:00',
                ],
            ], JSON_THROW_ON_ERROR)));

        $workspace = $sdk->createWorkspace([
            'repository_id' => 'repo-1',
            'prompt' => 'Fix bug',
        ]);

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertSame('wt-1', $workspace->id);
        $this->assertSame('repo-1', $workspace->repoId);
        $this->assertSame('srv-1', $workspace->serverId);
        $this->assertSame('fix-bug', $workspace->branch);
        $this->assertFalse($workspace->branchRenamed);
        $this->assertSame('/code/polyscope/.polyscope/worktrees/fix-bug', $workspace->path);
        $this->assertSame('main', $workspace->baseBranch);
        $this->assertSame('abc123', $workspace->baseCommit);
        $this->assertSame([], $workspace->linkedWorktreeIds);
    }

    public function test_repository_resource_can_create_workspace_without_manual_repository_id(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces', [
                'timeout' => 30,
                'json' => [
                    'prompt' => 'Fix bug',
                    'server_id' => 'srv-1',
                    'repository_id' => 'repo-1',
                ],
            ])
            ->andReturn(new Response(201, [], json_encode([
                'data' => [
                    'id' => 'wt-1',
                    'repo_id' => 'repo-1',
                    'branch' => 'fix-bug',
                    'status' => 'active',
                ],
            ], JSON_THROW_ON_ERROR)));

        $repository = new \Polyscope\Laravel\Resources\Repository([
            'id' => 'repo-1',
            'name' => 'polyscope',
            'path' => '/code/polyscope',
        ], $sdk);

        $workspace = $repository->createWorkspace([
            'prompt' => 'Fix bug',
            'server_id' => 'srv-1',
        ]);

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertSame('wt-1', $workspace->id);
        $this->assertSame('repo-1', $workspace->repoId);
    }

    public function test_repository_resource_can_create_workspace_from_prompt_string(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces', [
                'timeout' => 30,
                'json' => [
                    'prompt' => 'Fix bug',
                    'repository_id' => 'repo-1',
                ],
            ])
            ->andReturn(new Response(201, [], json_encode([
                'data' => [
                    'id' => 'wt-1',
                    'repo_id' => 'repo-1',
                    'branch' => 'fix-bug',
                    'status' => 'active',
                ],
            ], JSON_THROW_ON_ERROR)));

        $repository = new \Polyscope\Laravel\Resources\Repository([
            'id' => 'repo-1',
            'name' => 'polyscope',
            'path' => '/code/polyscope',
        ], $sdk);

        $workspace = $repository->createWorkspace('Fix bug');

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertSame('wt-1', $workspace->id);
        $this->assertSame('repo-1', $workspace->repoId);
    }

    public function test_repository_resource_rejects_mismatched_repository_id_when_creating_workspace(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The provided repository_id does not match this repository resource.');

        $sdk = new Polyscope('token');

        $repository = new \Polyscope\Laravel\Resources\Repository([
            'id' => 'repo-1',
            'name' => 'polyscope',
            'path' => '/code/polyscope',
        ], $sdk);

        $repository->createWorkspace([
            'repository_id' => 'repo-2',
            'prompt' => 'Fix bug',
        ]);
    }

    public function test_workspace_show_maps_nested_properties(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/workspaces/wt-1', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [
                    'id' => 'wt-1',
                    'repo_id' => 'repo-1',
                    'server_id' => 'srv-1',
                    'branch' => 'fix-bug',
                    'branch_renamed' => true,
                    'path' => '/code/polyscope/.polyscope/worktrees/fix-bug',
                    'status' => 'active',
                    'pr_number' => 12,
                    'pr_url' => 'https://github.com/beyondcode/polyscope/pull/12',
                    'pr_title' => 'Fix bug',
                    'pr_body' => 'This fixes a bug.',
                    'pr_is_draft' => false,
                    'issue_number' => 35,
                    'issue_title' => 'Broken behavior',
                    'issue_url' => 'https://github.com/beyondcode/polyscope/issues/35',
                    'base_branch' => 'main',
                    'base_commit' => 'abc123',
                    'linked_worktree_ids' => ['wt-2'],
                    'created_at' => '2026-02-25T18:00:00+00:00',
                    'repository' => [
                        'id' => 'repo-1',
                        'name' => 'polyscope',
                        'full_name' => 'beyondcode/polyscope',
                        'path' => '/code/polyscope',
                    ],
                    'agent' => [
                        'status' => 'working',
                    ],
                    'stats' => [
                        'files_changed' => 2,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)));

        $workspace = $sdk->workspace('wt-1');

        $this->assertSame('wt-1', $workspace->id);
        $this->assertSame('repo-1', $workspace->repoId);
        $this->assertSame('srv-1', $workspace->serverId);
        $this->assertTrue($workspace->branchRenamed);
        $this->assertSame(12, $workspace->prNumber);
        $this->assertSame('https://github.com/beyondcode/polyscope/pull/12', $workspace->prUrl);
        $this->assertSame(35, $workspace->issueNumber);
        $this->assertSame(['wt-2'], $workspace->linkedWorktreeIds);
        $this->assertIsArray($workspace->repository);
        $this->assertSame('repo-1', $workspace->repository['id']);
        $this->assertIsArray($workspace->agent);
        $this->assertSame('working', $workspace->agent['status']);
        $this->assertIsArray($workspace->stats);
        $this->assertSame(2, $workspace->stats['files_changed']);
    }

    public function test_workspace_messages_are_transformed(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/workspaces/wt-1/messages', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 1,
                        'session_id' => 'session-1',
                        'role' => 'user',
                        'content' => 'Fix this',
                        'metadata' => null,
                        'created_at' => '2026-02-25T18:10:00+00:00',
                    ],
                    [
                        'id' => 2,
                        'session_id' => 'session-1',
                        'role' => 'assistant',
                        'content' => 'Working on it',
                        'metadata' => '{"model":"gpt-5.3-codex"}',
                        'created_at' => '2026-02-25T18:10:10+00:00',
                    ],
                ],
                'meta' => [
                    'cursor' => 2,
                    'has_more' => false,
                ],
            ], JSON_THROW_ON_ERROR)));

        $messages = $sdk->workspaceMessages('wt-1');

        $this->assertInstanceOf(WorkspaceMessages::class, $messages);
        $this->assertCount(2, $messages->messages);
        $this->assertInstanceOf(Message::class, $messages->messages[0]);
        $this->assertSame(2, $messages->cursor);
        $this->assertFalse($messages->hasMore);
        $this->assertSame('session-1', $messages->messages[0]->sessionId);
        $this->assertSame('{"model":"gpt-5.3-codex"}', $messages->messages[1]->metadata);
        $this->assertSame('2026-02-25T18:10:10+00:00', $messages->messages[1]->createdAt);
    }

    public function test_workspace_diff_is_mapped_to_nested_resources(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/workspaces/wt-1/diff', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [
                    'diff' => "diff --git a/file.php b/file.php\n",
                    'files' => [
                        ['path' => 'file.php', 'additions' => 5, 'deletions' => 2],
                    ],
                    'stats' => [
                        'filesChanged' => 1,
                        'additions' => 5,
                        'deletions' => 2,
                    ],
                    'local' => [
                        'diff' => "diff --git a/file.php b/file.php\n",
                        'files' => [
                            ['path' => 'file.php', 'additions' => 5, 'deletions' => 2],
                        ],
                        'stats' => [
                            'filesChanged' => 1,
                            'additions' => 5,
                            'deletions' => 2,
                        ],
                    ],
                    'base' => [
                        'diff' => "diff --git a/file.php b/file.php\n",
                        'files' => [
                            ['path' => 'file.php', 'additions' => 5, 'deletions' => 2],
                        ],
                        'stats' => [
                            'filesChanged' => 1,
                            'additions' => 5,
                            'deletions' => 2,
                        ],
                    ],
                    'commitsAhead' => 3,
                ],
            ], JSON_THROW_ON_ERROR)));

        $diff = $sdk->workspaceDiff('wt-1');

        $this->assertInstanceOf(WorkspaceDiff::class, $diff);
        $this->assertSame("diff --git a/file.php b/file.php\n", $diff->diff);
        $this->assertCount(1, $diff->files);
        $this->assertSame('file.php', $diff->files[0]->path);
        $this->assertInstanceOf(DiffSnapshot::class, $diff->local);
        $this->assertInstanceOf(DiffSnapshot::class, $diff->base);
        $this->assertNotNull($diff->stats);
        $this->assertSame(1, $diff->stats->filesChanged);
        $this->assertSame(3, $diff->commitsAhead);
    }

    public function test_workspace_actions_return_generic_resource(): void
    {
        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces/wt-1/messages', [
                'timeout' => 30,
                'json' => [
                    'content' => 'Please continue',
                ],
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => [
                    'workspace_id' => 'wt-1',
                    'agent' => ['status' => 'working'],
                ],
            ], JSON_THROW_ON_ERROR)));

        $result = $sdk->sendWorkspaceMessage('wt-1', 'Please continue');

        $this->assertInstanceOf(GenericResource::class, $result);
        $this->assertSame('wt-1', $result->workspaceId);
        $this->assertIsArray($result->agent);
        $this->assertSame('working', $result->agent['status']);
    }

    public function test_validation_error_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('POST', 'v1/workspaces', [
                'timeout' => 30,
                'json' => ['prompt' => 'Missing repository_id'],
            ])
            ->andReturn(new Response(422, [], json_encode([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'repository_id' => ['The repository id field is required.'],
                ],
            ], JSON_THROW_ON_ERROR)));

        $sdk->createWorkspace([
            'prompt' => 'Missing repository_id',
        ]);
    }

    public function test_server_offline_response_throws_specific_exception(): void
    {
        $this->expectException(ServerOfflineException::class);

        $sdk = new Polyscope('token', $http = Mockery::mock(Client::class));

        $http->shouldReceive('request')
            ->once()
            ->with('GET', 'v1/workspaces/wt-1', [
                'timeout' => 30,
            ])
            ->andReturn(new Response(503, [], json_encode([
                'error' => [
                    'code' => 'server_offline',
                    'message' => 'The server for this workspace is currently offline.',
                ],
            ], JSON_THROW_ON_ERROR)));

        $sdk->workspace('wt-1');
    }
}
