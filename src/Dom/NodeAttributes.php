<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

class NodeAttributes
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * NodeAttributes constructor.
     */
    public function __construct(array $attrs)
    {
        for ($i = 0; $i <= \count($attrs) - 2; $i += 2) {
            $this->attributes[$attrs[$i]] = $attrs[$i + 1];
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function get($name): ?string
    {
        return $this->attributes[$name] ?? null;
    }
}
