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

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class BrowsingTest extends BaseTestCase
{
    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate(self::sitePath($file))->waitForNavigation();

        return $page;
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testPageNavigateEvaluate()
    {
        // initial navigation
        $page = $this->openSitePage('index.html');
        $title = $page->evaluate('document.title')->getReturnValue();
        $this->assertEquals('foo', $title);

        // navigate again
        $page->navigate(self::sitePath('a.html'))->waitForNavigation();
        $title = $page->evaluate('document.title')->getReturnValue();
        $this->assertEquals('a - test', $title);
    }

    public function testFormSubmission()
    {
        // initial navigation
        $page = $this->openSitePage('form.html');
        $evaluation = $page->evaluate(
            '(() => {
                document.querySelector("#myinput").value = "hello";
                setTimeout(() => {document.querySelector("#myform").submit();}, 300)
            })()'
        );

        $evaluation->waitForPageReload();
        $this->assertEquals('hello', $page->evaluate('document.querySelector("#value").innerHTML')->getReturnValue());
    }

    public function testGetCurrentUrl()
    {
        $page = self::$browser->createPage();

        $page->getSession()->getConnection()->readData();

        $this->assertEquals('about:blank', $page->getCurrentUrl());

        $page->navigate(self::sitePath('a.html'))->waitForNavigation();

        $this->assertEquals(self::sitePath('a.html'), $page->getCurrentUrl());
    }

    public function testPageNavigationLocalNotFoundUrl()
    {
        $factory = new BrowserFactory();
        $browser = $factory->createBrowser();

        $page = $browser->createPage();

        // for some reasons chrome creates a new loader when we navigate to a local non-existent file
        // here we are testing that feature with strict and non strict modes
        $page->navigate('file:///does-not-exist')->waitForNavigation();

        $this->assertTrue(true);
    }
}
