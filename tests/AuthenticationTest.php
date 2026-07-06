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
use HeadlessChromium\Exception\AuthenticationFailed;
use InvalidArgumentException;

/**
 * @covers \HeadlessChromium\Page
 */
class AuthenticationTest extends HttpEnabledTestCase
{
    private const AUTH_URL = 'basic-auth.php';
    private const VALID_USER = 'testuser';
    private const VALID_PASS = 'testpass';

    public static Browser\ProcessAwareBrowser $browser;

    public function setUp(): void
    {
        parent::setUp();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::$browser->close();
    }

    public function testAuthenticateAllowsAccessWithValidCredentials(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS);
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        // let's hit it twice to ensure subsequent requests also work
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateUpdatesCredentialsForNewChallenges(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS);
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());

        $page->authenticate('otheruser', 'otherpass');
        $page->navigate(self::sitePath(self::AUTH_URL.'?user=otheruser&pass=otherpass'))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateScopedToOriginAllowsMatchingOrigin(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS, 'HTTP://LOCALHOST:8083/ignored/path');
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateScopedToDifferentOriginDoesNotSendCredentials(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS, 'http://127.0.0.1:8083');
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringNotContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateThrowsOnInvalidOrigin(): void
    {
        $page = self::$browser->createPage();

        $this->expectException(InvalidArgumentException::class);

        $page->authenticate(self::VALID_USER, self::VALID_PASS, 'not an origin');
    }

    public function testClearAuthenticationStopsAnsweringChallenges(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS);
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());

        $page->clearAuthentication();

        // expect different credentials so the previously cached ones no longer authenticate
        $page->navigate(self::sitePath(self::AUTH_URL.'?user=otheruser&pass=otherpass'))->waitForNavigation();

        self::assertStringNotContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateThrowsOnInvalidCredentials(): void
    {
        $page = self::$browser->createPage();

        $page->authenticate('wrong', 'credentials');

        $this->expectException(AuthenticationFailed::class);

        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();
    }

    public function testAuthenticateWithoutCredentialsDeniesAccess(): void
    {
        $page = self::$browser->createPage();

        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringNotContainsString('Authenticated', $page->getHtml());
    }
}
