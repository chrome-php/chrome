<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class BrowserTest extends BaseTestCase
{

    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::$browser->close();
    }

    private function sitePath($file)
    {
        return 'file://' . __DIR__ . '/../resources/static-web/' . $file;
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate($this->sitePath($file))->waitForNavigation();

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
        $page->navigate($this->sitePath('a.html'))->waitForNavigation();
        $title = $page->evaluate('document.title')->getReturnValue();
        $this->assertEquals('a - test', $title);
    }
}
