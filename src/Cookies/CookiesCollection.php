<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Cookies;

class CookiesCollection implements \IteratorAggregate, \Countable
{

    /**
     * @var Cookie[]
     */
    protected $cookies = [];

    /**
     * CookiesCollection constructor.
     * @param array|null $cookies
     */
    public function __construct(array $cookies = null)
    {
        if ($cookies) {
            foreach ($cookies as $cookie) {
                if (is_array($cookie)) {
                    $cookie = new Cookie($cookie);
                }
                $this->addCookie($cookie);
            }
        }
    }

    /**
     * Adds a cookie
     * @param Cookie $cookie
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[] = $cookie;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->cookies);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->cookies);
    }

    /**
     * Get the cookie at the given index
     * @param $i
     * @return Cookie
     */
    public function getAt($i)
    {
        if (!isset($this->cookies[$i])) {
            throw new \RuntimeException(sprintf('No cookie at index %s', $i));
        }
        return $this->cookies[$i];
    }

    /**
     * Find cookies with matching values
     *
     * usage:
     *
     * ```
     * // find cookies having name == 'foo'
     * $newCookies = $cookies->filterBy('name', 'foo');
     *
     * // find cookies having domain == 'example.com'
     * $newCookies = $cookies->filterBy('domain', 'example.com');
     * ```
     *
     * @param string $param
     * @param string $value
     * @return CookiesCollection
     */
    public function filterBy($param, $value)
    {
        return new CookiesCollection(array_filter($this->cookies, function (Cookie $cookie) use ($param, $value) {
            return $cookie[$param] == $value;
        }));
    }

    /**
     * Find first cookies with matching value
     *
     * usage:
     *
     * ```
     * // find first cookie having name == 'foo'
     * $cookie = $cookies->findOneBy('name', 'foo');
     *
     * if ($cookie) {
     *   // do something
     * }
     * ```
     *
     * @param string $param
     * @param string $value
     * @return Cookie|null
     */
    public function findOneBy($param, $value)
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie[$param] == $value) {
                return $cookie;
            }
        }
        return null;
    }
}
