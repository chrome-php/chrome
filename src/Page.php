<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Session;

class Page
{

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get the session this page is attached to
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * Navigates to the given url
     * @param $url
     */
    public function navigate($url)
    {
        $this->session->sendMessageSync(new Message('Page.navigate', ['url' => $url]));
    }
}
