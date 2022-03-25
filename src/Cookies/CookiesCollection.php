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

class CookiesCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var Cookie[]
     */
    protected $cookies = [];

    /**
     * CookiesCollection constructor.
     */
    public function __construct(array $cookies = null)
    {
        if ($cookies) {
            foreach ($cookies as $cookie) {
                if (\is_array($cookie)) {
                    $cookie = new Cookie($cookie);
                }
                $this->addCookie($cookie);
            }
        }
    }

    /**
     * Adds a cookie.
     */
    public function addCookie(Cookie $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->cookies);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->cookies);
    }

    /**
     * Get the cookie at the given index.
     *
     * @param int $i
     *
     * @return Cookie
     */
    public function getAt($i): Cookie
    {
        if (!isset($this->cookies[$i])) {
            throw new \RuntimeException(\sprintf('No cookie at index %s', $i));
        }

        return $this->cookies[$i];
    }

    /**
     * Find cookies with matching values.
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
     *
     * @return CookiesCollection
     */
    public function filterBy(string $param, string $value)
    {
        return new self(\array_filter($this->cookies, function (Cookie $cookie) use ($param, $value) {
            return $cookie[$param] == $value;
        }));
    }

    /**
     * Find first cookies with matching value.
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
     *
     * @return Cookie|null
     */
    public function findOneBy(string $param, string $value)
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie[$param] == $value) {
                return $cookie;
            }
        }

        return null;
    }
}
