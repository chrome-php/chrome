<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

class NodeAttributes
{
    private $attributes = [];

    public function __construct($attrs)
    {
        for ($i = 0; $i < count($attrs) - 2; $i += 2) {
            $this->attributes[$attrs[$i]] = $attrs[$i + 1];
        }
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function has($name)
    {
        return isset($this->attributes[$name]);
    }

    public function get($name)
    {
        return $this->attributes[$name] ?? null;
    }
}
