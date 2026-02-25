<?php

declare(strict_types=1);

namespace Polyscope\Laravel\Resources;

use JsonSerializable;
use Polyscope\Laravel\Polyscope;

#[\AllowDynamicProperties]
class Resource implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
        protected ?Polyscope $polyscope = null,
    ) {
        $this->fill();
    }

    protected function fill(): void
    {
        foreach ($this->attributes as $key => $value) {
            $this->{$this->camelCase($key)} = $value;
        }
    }

    protected function camelCase(string $key): string
    {
        $parts = explode('_', str_replace('-', '_', $key));

        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $parts[$index] = ucfirst($part);
            }
        }

        return implode('', $parts);
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
            return new $class($attributes + $extraData, $this->polyscope);
        }, $collection);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
