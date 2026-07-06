<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

use InvalidArgumentException;

/**
 * Restricts authentication credentials to a single web origin.
 */
class AuthenticationScope
{
    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param string $origin an absolute http or https URI; any path, query or fragment is ignored
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $origin)
    {
        $parts = \parse_url($origin);

        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('The authentication origin must be an absolute http or https URI.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('The authentication origin must not contain user info.');
        }

        $scheme = \strtolower($parts['scheme']);

        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('The authentication origin must use the http or https scheme.');
        }

        $host = \strtolower($parts['host']);

        // colons are only valid within bracketed IPv6 literals
        if ('[' === \substr($host, 0, 1)) {
            $validHost = 1 === \preg_match('/^\[[0-9a-f:.]+\]$/D', $host);
        } else {
            $validHost = 1 === \preg_match('/^[a-z0-9\-._~%]+$/D', $host);
        }

        if (!$validHost) {
            throw new InvalidArgumentException('The authentication origin host contains invalid characters.');
        }

        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $parts['port'] ?? ('https' === $scheme ? 443 : 80);
    }

    /**
     * Check whether the given origin, e.g. of an authentication challenge, is the same web origin.
     */
    public function matchesOrigin(string $origin): bool
    {
        try {
            $other = new self($origin);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return $other->scheme === $this->scheme && $other->host === $this->host && $other->port === $this->port;
    }

    /**
     * Get the fetch request pattern matching only URLs within this origin.
     */
    public function getUrlPattern(): string
    {
        $pattern = $this->scheme.'://'.$this->host;

        // browsers omit default ports in request URLs
        if (('http' === $this->scheme && 80 !== $this->port) || ('https' === $this->scheme && 443 !== $this->port)) {
            $pattern .= ':'.$this->port;
        }

        return $pattern.'/*';
    }
}
