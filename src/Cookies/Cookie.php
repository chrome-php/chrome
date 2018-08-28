<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Cookies;

class Cookie implements \ArrayAccess
{

    /**
     * @var array
     */
    protected $data;

    /**
     * Cookie constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->offsetGet('value');
    }

    /**
     * @return mixed|null
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Cannot set cookie values');
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Cannot unset cookie values');
    }
}
