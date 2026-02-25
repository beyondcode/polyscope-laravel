<?php

declare(strict_types=1);

namespace Polyscope\Laravel;

use Polyscope\Laravel\Exceptions\ApiException;
use Polyscope\Laravel\Exceptions\AuthenticationException;
use Polyscope\Laravel\Exceptions\ForbiddenException;
use Polyscope\Laravel\Exceptions\MissingApiTokenException;
use Polyscope\Laravel\Exceptions\NotFoundException;
use Polyscope\Laravel\Exceptions\RateLimitExceededException;
use Polyscope\Laravel\Exceptions\ServerOfflineException;
use Polyscope\Laravel\Exceptions\ServerTimeoutException;
use Polyscope\Laravel\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

trait MakesHttpRequests
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $uri, array $query = []): mixed
    {
        return $this->request('GET', $uri, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function post(string $uri, array $payload = [], array $options = []): mixed
    {
        return $this->request('POST', $uri, $payload, $options);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function put(string $uri, array $payload = [], array $options = []): mixed
    {
        return $this->request('PUT', $uri, $payload, $options);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function delete(string $uri, array $payload = [], array $options = []): mixed
    {
        return $this->request('DELETE', $uri, $payload, $options);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    protected function request(string $verb, string $uri, array $payload = [], array $options = []): mixed
    {
        if ($this->guzzle === null) {
            throw new MissingApiTokenException();
        }

        $requestOptions = [];

        if (isset($options['timeout'])) {
            $requestOptions['timeout'] = (int) $options['timeout'];
        } elseif (isset($this->timeout)) {
            $requestOptions['timeout'] = (int) $this->timeout;
        }

        if (isset($options['headers']) && is_array($options['headers'])) {
            $requestOptions['headers'] = $options['headers'];
        }

        if ($verb === 'GET') {
            if ($payload !== []) {
                $requestOptions['query'] = $payload;
            }
        } elseif (isset($options['multipart']) && is_array($options['multipart'])) {
            $requestOptions['multipart'] = $options['multipart'];
        } elseif (($options['as_form'] ?? false) === true) {
            if ($payload !== []) {
                $requestOptions['form_params'] = $payload;
            }
        } elseif ($payload !== []) {
            $requestOptions['json'] = $payload;
        }

        $response = $this->guzzle->request($verb, ltrim($uri, '/'), $requestOptions);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 204) {
            return null;
        }

        $body = $this->decodeResponseBody($response);

        if ($statusCode < 200 || $statusCode > 299) {
            $this->handleRequestError($response, $body);
        }

        return $body;
    }

    protected function decodeResponseBody(ResponseInterface $response): mixed
    {
        $responseBody = (string) $response->getBody();

        if ($responseBody === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $responseBody;
    }

    protected function handleRequestError(ResponseInterface $response, mixed $body): never
    {
        $statusCode = $response->getStatusCode();
        $message = "Request failed with status {$statusCode}.";
        $errorCode = null;

        if (is_array($body)) {
            $message = (string) ($body['message'] ?? $body['error']['message'] ?? $message);
            $errorCode = $body['error']['code'] ?? null;
        } elseif (is_string($body) && trim($body) !== '') {
            $message = $body;
        }

        if ($statusCode === 422) {
            throw new ValidationException(
                is_array($body) ? (array) ($body['errors'] ?? $body) : [],
                $message
            );
        }

        if ($statusCode === 401) {
            throw new AuthenticationException($message, $body);
        }

        if ($statusCode === 403) {
            throw new ForbiddenException($message, $body);
        }

        if ($statusCode === 404) {
            throw new NotFoundException($message, $body);
        }

        if ($statusCode === 429) {
            $reset = $response->hasHeader('x-ratelimit-reset')
                ? (int) $response->getHeader('x-ratelimit-reset')[0]
                : null;

            throw new RateLimitExceededException($message, $body, $reset);
        }

        if ($statusCode === 503 || $errorCode === 'server_offline') {
            throw new ServerOfflineException($message, $body);
        }

        if ($statusCode === 504 || $errorCode === 'server_timeout') {
            throw new ServerTimeoutException($message, $body);
        }

        throw new ApiException($message, $statusCode, $errorCode, $body);
    }
}
