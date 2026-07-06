<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test\PageUtils;

use HeadlessChromium\PageUtils\AuthenticationScope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\PageUtils\AuthenticationScope
 */
class AuthenticationScopeTest extends TestCase
{
    public function testNormalizesFullUrlToOrigin(): void
    {
        $scope = new AuthenticationScope('HTTP://Example.COM/protected?x=1#fragment');

        self::assertTrue($scope->matchesOrigin('http://example.com'));
        self::assertSame('http://example.com/*', $scope->getUrlPattern());
    }

    public function testDefaultPortsAreEquivalent(): void
    {
        self::assertTrue((new AuthenticationScope('http://example.com:80'))->matchesOrigin('http://example.com'));
        self::assertTrue((new AuthenticationScope('https://example.com'))->matchesOrigin('https://example.com:443'));
        self::assertSame('http://example.com/*', (new AuthenticationScope('http://example.com:80'))->getUrlPattern());
    }

    public function testKeepsNonDefaultPort(): void
    {
        $scope = new AuthenticationScope('http://example.com:8080/path');

        self::assertTrue($scope->matchesOrigin('http://example.com:8080'));
        self::assertSame('http://example.com:8080/*', $scope->getUrlPattern());
    }

    public function testIpv6Origin(): void
    {
        $scope = new AuthenticationScope('http://[::1]:8083/protected');

        self::assertTrue($scope->matchesOrigin('http://[::1]:8083'));
        self::assertFalse($scope->matchesOrigin('http://[::2]:8083'));
    }

    /**
     * @dataProvider mismatchingOriginProvider
     */
    public function testDoesNotMatchDifferentOrigin(string $origin): void
    {
        $scope = new AuthenticationScope('http://example.com:8080');

        self::assertFalse($scope->matchesOrigin($origin));
    }

    public static function mismatchingOriginProvider(): array
    {
        return [
            'different port' => ['http://example.com:8081'],
            'different scheme' => ['https://example.com:8080'],
            'different host' => ['http://other.example.com:8080'],
            'default port' => ['http://example.com'],
            'opaque origin' => ['null'],
            'empty origin' => [''],
        ];
    }

    /**
     * @dataProvider invalidOriginProvider
     */
    public function testRejectsInvalidOrigin(string $origin): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthenticationScope($origin);
    }

    public static function invalidOriginProvider(): array
    {
        return [
            'missing scheme' => ['example.com'],
            'scheme relative' => ['//example.com'],
            'missing host' => ['http:'],
            'empty host' => ['http://'],
            'double port' => ['http://example.com:80:90'],
            'unsupported scheme' => ['ftp://example.com'],
            'user info' => ['http://user:pass@example.com'],
            'wildcard host' => ['http://*.example.com'],
            'space in host' => ['http://exam ple.com'],
        ];
    }
}
