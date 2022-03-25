<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    public function __construct(array $data)
    {
        if (isset($data['expires']) && \is_string($data['expires']) && !\is_numeric($data['expires'])) {
            $data['expires'] = \strtotime($data['expires']);
        }

        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->offsetGet('value');
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->offsetGet('domain');
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('Cannot set cookie values');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Cannot unset cookie values');
    }

    /**
     * @param string $name
     * @param string $value
     * @param array  $params
     *
     * @return Cookie
     */
    public static function create($name, $value, array $params = [])
    {
        $params['name'] = $name;
        $params['value'] = $value;

        return new self($params);
    }
}
