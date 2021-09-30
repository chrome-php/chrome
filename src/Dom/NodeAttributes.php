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
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function get($name)
    {
        return $this->attributes[$name] ?? null;
    }
}
