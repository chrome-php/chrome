<?php
/**
 * @license see LICENSE
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

    public function testFilterBy()
    {

        $cookies = new CookiesCollection([
            Cookie::create('foo', 'bar'),
            Cookie::create('foo', 'baz'),
            Cookie::create('qux', 'quux'),
        ]);

        $newCookies = $cookies->filterBy('name', 'foo');

        $this->assertCount(2, $newCookies);
        $this->assertEquals('bar', $newCookies->getAt(0)->getValue());
        $this->assertEquals('baz', $newCookies->getAt(1)->getValue());
    }


    public function testFindOneBy()
    {

        $cookies = new CookiesCollection([
            Cookie::create('foo', 'bar'),
            Cookie::create('foo', 'baz'),
            Cookie::create('qux', 'quux'),
        ]);

        $cookieFoo = $cookies->findOneBy('name', 'foo');
        $cookieQux = $cookies->findOneBy('name', 'qux');

        $this->assertEquals('bar', $cookieFoo->getValue());
        $this->assertEquals('quux', $cookieQux->getValue());
    }
}
