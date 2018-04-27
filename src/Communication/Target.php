<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

use HeadlessChromium\Exception\TargetDestroyed;

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

    protected $destroyed = false;

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
        if ($this->destroyed) {
            throw new TargetDestroyed('The target was destroyed.');
        }
        return $this->session;
    }

    /**
     * Marks the target as destroyed
     * @internal
     */
    public function destroy()
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The target was already destroyed.');
        }
        $this->session->destroy();
        $this->session = null;
        $this->destroyed = true;
    }

    /**
     * @return bool
     */
    public function isDestroyed(): bool
    {
        return $this->destroyed;
    }
}
