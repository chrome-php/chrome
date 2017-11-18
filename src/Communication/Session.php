<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

class Session
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
    
    public function __construct(string $targetId, string $sessionId, Connection $connection)
    {
        $this->sessionId  = $sessionId;
        $this->targetId   = $targetId;
        $this->connection = $connection;
    }

    /**
     * @param Message $message
     * @return ResponseReader
     * @throws \HeadlessChromium\Exception\CommunicationException
     */
    public function sendMessage(Message $message)
    {
        return $this->connection->sendMessage(new Message('Target.sendMessageToTarget', [
            'message' => (string) $message,
            'sessionId' => $this->getSessionId()
        ]));
    }

    /**
     * @param Message $message
     * @return Response
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function sendMessageSync(Message $message)
    {
        return $this->connection->sendMessageSync(new Message('Target.sendMessageToTarget', [
            'message' => (string) $message,
            'sessionId' => $this->getSessionId()
        ]));
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
        return $this->connection;
    }
}
