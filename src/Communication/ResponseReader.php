<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Utils;
use HeadlessChromium\Exception\OperationTimedOut;

class ResponseReader
{

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Response|null
     */
    protected $response = null;

    /**
     * Response constructor.
     * @param Message $message
     * @param Connection $connection
     */
    public function __construct(Message $message, Connection $connection)
    {
        $this->message = $message;
        $this->connection = $connection;
    }

    /**
     * True if a response is available
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * the message to get a response for
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * The connection to check messages for
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get the response
     *
     * Note: response will always be missing until checkForResponse is called
     * and the response is available in the buffer
     *
     * @return Response
     * @throws NoResponseAvailable
     */
    public function getResponse(): Response
    {
        if (!$this->response) {
            throw new NoResponseAvailable('Response is not available. Try to use the method waitForResponse instead.');
        }

        return $this->response;
    }

    /**
     * Wait for a response
     * @param int $timeout time to wait for a response (milliseconds)
     * @return Response
     *
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     */
    public function waitForResponse(int $timeout = null): Response
    {
        if ($this->hasResponse()) {
            return $this->getResponse();
        }

        // default 2000ms
        $timeout = $timeout ?? 2000;

        // TODO replace with Utils::tryWithTimeout
        return Utils::tryWithTimeout($timeout * 1000, $this->waitForResponseGenerator());
    }

    /**
     * To be used in waitForResponse method
     * @return \Generator|Response
     * @throws NoResponseAvailable
     * @internal
     */
    private function waitForResponseGenerator()
    {
        while (true) {

            // 50 microseconds between each iteration
            $tryDelay = 50;

            // read available response
            $hasResponse = $this->checkForResponse();

            // if found return it
            if ($hasResponse) {
                return $this->getResponse();
            }

            // wait before next check
            yield $tryDelay;
        }
    }

    /**
     * Check in the connection if a response exists for the message and store it if the response exists.
     * @return bool
     */
    public function checkForResponse()
    {
        // if response is already read, ignore
        if ($this->hasResponse()) {
            return true;
        }

        $id = $this->message->getId();

        // if response exists store it
        if ($this->connection->hasResponseForId($id)) {
            $this->response = new Response($this->connection->getResponseForId($id), $this->message);
            return true;
        }

        // read data
        $this->connection->readData();

        // if response store it
        if ($this->connection->hasResponseForId($id)) {
            $this->response = new Response($this->connection->getResponseForId($id), $this->message);
            return true;
        }

        return false;
    }
}
