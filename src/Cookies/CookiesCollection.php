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
    public function getAt($i): Cookie
    {
        if (!isset($this->cookies[$i])) {
            throw new \RuntimeException(sprintf('No cookie at index %s', $i));
        }
        return $this->cookies[$i];
    }
}
