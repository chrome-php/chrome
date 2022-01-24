<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var Session|null
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
     */
    public function __construct(array $targetInfo, Connection $connection)
    {
        $this->targetInfo = $targetInfo;
        $this->connection = $connection;
    }

    /**
     * @param ?string $sessionId
     *
     * @return Session
     */
    public function getSession(?string $sessionId = null): Session
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The target was destroyed.');
        }

        // if not already done, create a session for the target
        if (!$this->session) {
            $this->session = $this->connection->createSession($this->getTargetInfo('targetId'), $sessionId);
        }

        return $this->session;
    }

    /**
     * Marks the target as destroyed.
     *
     * @internal
     */
    public function destroy(): void
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
    public function isDestroyed(): bool
    {
        return $this->destroyed;
    }

    /**
     * Get target info value by it's name or null if it does not exist.
     *
     * @param string $infoName
     *
     * @return mixed
     */
    public function getTargetInfo($infoName)
    {
        return $this->targetInfo[$infoName] ?? null;
    }

    /**
     * To be called when Target.targetInfoChanged is triggered.
     *
     * @param array $targetInfo
     *
     * @internal
     */
    public function targetInfoChanged($targetInfo): void
    {
        $this->targetInfo = $targetInfo;
    }
}
