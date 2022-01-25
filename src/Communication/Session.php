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

use Evenement\EventEmitter;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\TargetDestroyed;

class Session extends EventEmitter
{
    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $targetId;

    /**
     * @var Connection|null
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $destroyed = false;

    /**
     * Session constructor.
     *
     * @param string     $targetId
     * @param string     $sessionId
     * @param Connection $connection
     */
    public function __construct(string $targetId, string $sessionId, Connection $connection)
    {
        $this->sessionId = $sessionId;
        $this->targetId = $targetId;
        $this->connection = $connection;
    }

    /**
     * @param Message $message
     *
     * @throws CommunicationException
     *
     * @return SessionResponseReader
     */
    public function sendMessage(Message $message): SessionResponseReader
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The session was destroyed.');
        }

        $topResponse = $this->getConnection()->sendMessage(new Message('Target.sendMessageToTarget', [
            'message' => (string) $message,
            'sessionId' => $this->getSessionId(),
        ]));

        return new SessionResponseReader($topResponse, $message);
    }

    /**
     * @param Message $message
     * @param int     $timeout
     *
     * @throws NoResponseAvailable
     * @throws CommunicationException
     *
     * @return Response
     */
    public function sendMessageSync(Message $message, int $timeout = null): Response
    {
        $responseReader = $this->sendMessage($message);

        $response = $responseReader->waitForResponse($timeout ?? $this->getConnection()->getSendSyncDefaultTimeout());

        if (!$response) {
            throw new NoResponseAvailable('No response was sent in the given timeout');
        }

        return $response;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The session was destroyed.');
        }

        return $this->connection;
    }

    /**
     * Marks the session as destroyed.
     *
     * @internal
     */
    public function destroy(): void
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The session was already destroyed.');
        }
        $this->emit('destroyed');
        $this->connection = null;
        $this->removeAllListeners();
        $this->destroyed = true;
    }
}
