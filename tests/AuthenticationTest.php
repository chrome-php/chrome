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

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\AuthenticationFailed;

/**
 * @covers \HeadlessChromium\Page::authenticate
 */
class AuthenticationTest extends HttpEnabledTestCase
{
    private const AUTH_URL = 'basic-auth.php';
    private const VALID_USER = 'testuser';
    private const VALID_PASS = 'testpass';

    public function testAuthenticateAllowsAccessWithValidCredentials(): void
    {
        $page = (new BrowserFactory())->createBrowser()->createPage();

        $page->authenticate(self::VALID_USER, self::VALID_PASS);
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        // let's hit it twice to ensure subsequent requests also work
        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringContainsString('Authenticated', $page->getHtml());
    }

    public function testAuthenticateThrowsOnInvalidCredentials(): void
    {
        $this->expectException(AuthenticationFailed::class);

        $page = (new BrowserFactory())->createBrowser()->createPage();

        $page->authenticate('wrong', 'credentials');

        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();
    }

    public function testAuthenticateWithoutCredentialsDeniesAccess(): void
    {
        $page = (new BrowserFactory())->createBrowser()->createPage();

        $page->navigate(self::sitePath(self::AUTH_URL))->waitForNavigation();

        self::assertStringNotContainsString('Authenticated', $page->getHtml());
    }
}
