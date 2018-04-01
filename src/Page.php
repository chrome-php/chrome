<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Target;

class Page
{

    /**
     * @var Target
     */
    protected $target;

    public function __construct(Target $target)
    {
        $this->target = $target;
    }

    /**
     * Get the session this page is attached to
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->target->getSession();
    }

    /**
     * @param $url
     * @return Communication\Response
     * @throws Exception\NoResponseAvailable
     */
    public function navigate($url)
    {
        return $this->getSession()->sendMessageSync(new Message('Page.navigate', ['url' => $url]));
    }
}
