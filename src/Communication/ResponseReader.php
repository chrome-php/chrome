<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

use HeadlessChromium\Exception\NoResponseAvailable;

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
     * Get the response or an empty array if the response is not available.
     *
     * Note: response will always be empty until checkForResponse is called and the response is available in the buffer
     *
     * @return Response
     * @throws NoResponseAvailable
     *
     */
    public function getResponse(): Response
    {
        if (!$this->response) {
            throw new NoResponseAvailable('Response is not available');
        }

        return $this->response;
    }

    /**
     * Wait for a response
     * @param int $timeout time to wait for a response (milliseconds)
     * @return Response|null the response or null if no responses were found before the given timeout is reached
     */
    public function waitForResponse(int $timeout = null)
    {

        if ($this->hasResponse()) {
            return $this->getResponse();
        }

        // default 2000ms
        $timeout = $timeout ?? 2000;

        // 10 microseconds between each iteration
        $tryDelay = 10;

        // time to wait for the response
        $waitUntil = microtime(true) + $timeout / 1000;

        do {
            // read available response
            $hasResponse = $this->checkForResponse();

            // if found return it
            if ($hasResponse) {
                return $this->getResponse();
            }

            // wait before next check
            usleep($tryDelay);
        } while (microtime(true) < $waitUntil);

        return null;
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
