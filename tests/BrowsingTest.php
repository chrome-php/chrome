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
    public static Browser\ProcessAwareBrowser $browser;

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
    public function testPageNavigateEvaluate(): void
    {
        // initial navigation
        $page = $this->openSitePage('index.html');
        $title = $page->evaluate('document.title')->getReturnValue();
        self::assertEquals('foo', $title);

        // navigate again
        $page->navigate(self::sitePath('a.html'))->waitForNavigation();
        $title = $page->evaluate('document.title')->getReturnValue();
        self::assertEquals('a - test', $title);
    }

    public function testFormSubmission(): void
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
        self::assertEquals('hello', $page->evaluate('document.querySelector("#value").innerHTML')->getReturnValue());
    }

    public function testGetCurrentUrl(): void
    {
        $page = self::$browser->createPage();

        $page->getSession()->getConnection()->readData();

        self::assertEquals('about:blank', $page->getCurrentUrl());

        $page->navigate(self::sitePath('a.html'))->waitForNavigation();

        self::assertEquals(self::sitePath('a.html'), $page->getCurrentUrl());
    }

    public function testPageNavigationLocalNotFoundUrl(): void
    {
        $factory = new BrowserFactory();
        $browser = $factory->createBrowser();

        $page = $browser->createPage();

        // for some reasons chrome creates a new loader when we navigate to a local non-existent file
        // here we are testing that feature with strict and non strict modes
        $page->navigate('file:///does-not-exist')->waitForNavigation();

        self::assertTrue(true);
    }

    public function testGetPages(): void
    {
        $initialCount = \count(self::$browser->getPages());

        self::$browser->createPage();

        $finalCount = \count(self::$browser->getPages());

        self::assertGreaterThan($initialCount, $finalCount);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testGetPagesNavigateEvaluate(): void
    {
        self::$browser->createPage();

        $pages = self::$browser->getPages();

        foreach ($pages as $page) {
            // initial navigation
            $page = $this->openSitePage('index.html');
            $title = $page->evaluate('document.title')->getReturnValue();
            self::assertEquals('foo', $title);

            // navigate again
            $page->navigate(self::sitePath('a.html'))->waitForNavigation();
            $title = $page->evaluate('document.title')->getReturnValue();
            self::assertEquals('a - test', $title);
        }
    }

    public function testGetPagesClose(): void
    {
        self::$browser->createPage();
        $page = self::$browser->createPage();

        $initialCount = \count(self::$browser->getPages());

        $page->close();

        $finalCount = \count(self::$browser->getPages());

        self::assertLessThan($initialCount, $finalCount);
    }
}
