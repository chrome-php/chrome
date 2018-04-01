<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

class Target
{
    /**
     * @var array
     */
    protected $targetInfo;

    /**
     * @var Session
     */
    protected $session;

    /**
     * Target constructor.
     * @param array $targetInfo
     * @param Session $session
     */
    public function __construct(array $targetInfo, Session $session)
    {
        $this->targetInfo = $targetInfo;
        $this->session = $session;
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }
}
