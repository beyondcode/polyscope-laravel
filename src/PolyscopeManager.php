<?php

declare(strict_types=1);

namespace Polyscope\Laravel;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Polyscope\Laravel\Polyscope
 */
class PolyscopeManager
{
    use ForwardsCalls;

    protected Polyscope $polyscope;

    public function __construct(?string $token = null, ?string $baseUrl = null, ?HttpClient $guzzle = null)
    {
        $this->polyscope = new Polyscope($token, $guzzle, $baseUrl);
    }

    public function client(): Polyscope
    {
        return $this->polyscope;
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->polyscope, $method, $parameters);
    }
}
