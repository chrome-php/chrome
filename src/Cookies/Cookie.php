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
        if (isset($data['expires'])  && is_string($data['expires']) && !is_numeric($data['expires'])) {
            $data['expires'] = strtotime($data['expires']);
        }

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
     * @return mixed|null
     */
    public function getDomain()
    {
        return $this->offsetGet('domain');
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
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

    /**
     * @param $name
     * @param $value
     * @param $params
     * @return Cookie
     */
    public static function create($name, $value, array $params = [])
    {
        $params['name'] = $name;
        $params['value'] = $value;
        return new Cookie($params);
    }
}
