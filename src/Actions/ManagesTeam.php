<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Actions;

use Polyscope\Laravel\Resources\GenericResource;

trait ManagesTeam
{
    public function inviteTeamMember(string $email): GenericResource
    {
        $response = $this->post('v1/team/invites', [
            'email' => $email,
        ]);

        return new GenericResource(
            is_array($response) ? ($response['data'] ?? $response) : [],
            $this,
        );
    }
}
