<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

class NodeAttributes
{
    /**
     * @var array
     */
    private $attributes = [];

    public function __construct(array $attrs)
    {
        for ($i = 0; $i <= \count($attrs) - 2; $i += 2) {
            $this->attributes[$attrs[$i]] = $attrs[$i + 1];
        }
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function has(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function get(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }
}
