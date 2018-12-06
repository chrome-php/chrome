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

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var bool
     */
    protected $destroyed = false;

    /**
     * Target constructor.
     * @param array $targetInfo
     * @param Session $session
     */
    public function __construct(array $targetInfo, Connection $connection)
    {
        $this->targetInfo = $targetInfo;
        $this->connection = $connection;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The target was destroyed.');
        }

        // if not already done, create a session for the target
        if (!$this->session) {
            $this->session = $session = $this->connection->createSession($this->getTargetInfo('targetId'));
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

        if ($this->session) {
            $this->session->destroy();
            $this->session = null;
        }

        $this->destroyed = true;
    }

    /**
     * @return bool
     */
    public function isDestroyed()
    {
        return $this->destroyed;
    }

    /**
     * Get target info value by it's name or null if it does not exist
     * @param $infoName
     * @return mixed|null
     */
    public function getTargetInfo($infoName)
    {
        return isset($this->targetInfo[$infoName]) ? $this->targetInfo[$infoName] : null;
    }

    /**
     * To be called when Target.targetInfoChanged is triggered.
     * @param $targetInfo
     * @internal
     */
    public function targetInfoChanged($targetInfo)
    {
        $this->targetInfo = $targetInfo;
    }
}
