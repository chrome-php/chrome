<?php
/**
 * @license see LICENSE
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
     * @var Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $destroyed = false;

    /**
     * Session constructor.
     * @param string $targetId
     * @param string $sessionId
     * @param Connection $connection
     */
    public function __construct($targetId, $sessionId, Connection $connection)
    {
        $this->sessionId  = $sessionId;
        $this->targetId   = $targetId;
        $this->connection = $connection;
    }

    /**
     * @param Message $message
     * @return SessionResponseReader
     * @throws CommunicationException
     */
    public function sendMessage(Message $message)
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The session was destroyed.');
        }

        $topResponse = $this->connection->sendMessage(new Message('Target.sendMessageToTarget', [
            'message' => (string) $message,
            'sessionId' => $this->getSessionId()
        ]));

        return new SessionResponseReader($topResponse, $message);
    }

    /**
     * @param Message $message
     * @param int $timeout
     * @return Response
     * @throws NoResponseAvailable
     * @throws CommunicationException
     */
    public function sendMessageSync(Message $message, $timeout = null)
    {
        $responseReader = $this->sendMessage($message);

        $response = $responseReader->waitForResponse(isset($timeout) ? $timeout : $this->connection->getSendSyncDefaultTimeout());

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
     * Marks the session as destroyed
     * @internal
     */
    public function destroy()
    {
        if ($this->destroyed) {
            throw new TargetDestroyed('The session was already destroyed.');
        }
        $this->emit('destroyed');
        $this->connection = null;
        $this->removeAllListeners();
    }
}
