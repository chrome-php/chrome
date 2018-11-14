<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Connection;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\PageUtils\CookiesGetter
 * @covers \HeadlessChromium\Page
 */
class HttpBrowsingTest extends HttpEnabledTestCase
{

    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public function setUp()
    {
        parent::setUp();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate($this->sitePath($file))->waitForNavigation();

        return $page;
    }


    /**
     * @link https://github.com/chrome-php/headless-chromium-php/issues/38
     */
    public function testServiceWorkerInstantlyDestroying()
    {
        $factory = new BrowserFactory();
        $browser = $factory->createBrowser();

        $page = $browser->createPage();
        $page->navigate($this->sitePath('create-and-destroy-target.html'))->waitForNavigation();

        $page = $browser->createPage();
        $page->navigate($this->sitePath('create-and-destroy-target.html'))->waitForNavigation();

        // helper to track that service worker was created
        $helper = new \stdClass();
        $helper->created = false;
        $helper->targetId = null;
        $helper->destroyed = false;

        // track created
        $page->getSession()->getConnection()->on(Connection::EVENT_TARGET_CREATED, function ($e) use ($helper) {
            if (isset($e['targetInfo']['type']) && $e['targetInfo']['type'] == 'service_worker') {
                $helper->created = true;
                $helper->targetId = $e['targetInfo']['targetId'];
            }
        });

        // track destroyed
        $page->getSession()->getConnection()->on(Connection::EVENT_TARGET_DESTROYED, function ($e) use ($helper) {
            if ($e['targetId'] == $helper->targetId) {
                $helper->destroyed = true;
            }
        });

        sleep(1);

        // do something to trigger service worker create/destroy
        $page->evaluate('document.language')->getReturnValue();

        // assert created/destroyed
        // Note: sometimes there are races conditions and this test needs to be run again
        $this->assertTrue($helper->created);
        $this->assertTrue($helper->destroyed);
        // Note: if that stops to assert, it could mean that chrome changed the way it works and that this test is
        // no longer required
    }
}
