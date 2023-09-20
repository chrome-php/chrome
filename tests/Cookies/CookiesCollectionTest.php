<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Cookies\CookiesCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Cookies\Cookie
 * @covers \HeadlessChromium\Cookies\CookiesCollection
 */
class CookiesCollectionTest extends TestCase
{
    public function testFilterBy(): void
    {
        $cookies = new CookiesCollection([
            Cookie::create('foo', 'bar'),
            Cookie::create('foo', 'baz'),
            Cookie::create('qux', 'quux'),
        ]);

        $newCookies = $cookies->filterBy('name', 'foo');

        self::assertCount(2, $newCookies);
        self::assertSame('bar', $newCookies->getAt(0)->getValue());
        self::assertSame('baz', $newCookies->getAt(1)->getValue());
    }

    public function testFindOneBy(): void
    {
        $cookies = new CookiesCollection([
            Cookie::create('foo', 'bar'),
            Cookie::create('foo', 'baz'),
            Cookie::create('qux', 'quux'),
        ]);

        $cookieFoo = $cookies->findOneBy('name', 'foo');
        $cookieQux = $cookies->findOneBy('name', 'qux');

        self::assertSame('bar', $cookieFoo->getValue());
        self::assertSame('quux', $cookieQux->getValue());
    }
}
