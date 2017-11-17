<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication;

use HeadlessChromium\Communication\Socket\SocketInterface;
use HeadlessChromium\Communication\Socket\Wrench;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\NoResponseAvailable;
use Wrench\Client as WrenchBaseClient;

class Connection
{

    /**
     * When strict mode is enabled communication error will result in exceptions
     * @var bool
     */
    protected $strict = true;

    /**
     * @var SocketInterface
     */
    protected $wsClient;

    protected $responseBuffer = [];

    /**
     * Default timeout for send sync in ms
     * @var int
     */
    protected $sendSyncDefaultTimeout = 10000;

    /**
     * CommunicationChannel constructor.
     * @param SocketInterface|string $socketClient
     */
    public function __construct($socketClient)
    {

        if (is_string($socketClient)) {
            $socketClient = new Wrench(new WrenchBaseClient($socketClient, 'http://127.0.0.1'));
        } elseif (!is_object($socketClient) && !$socketClient instanceof SocketInterface) {
            throw new \InvalidArgumentException(
                '$socketClient param should be either a SockInterface instance or a web socket uri string'
            );
        }

        $this->wsClient = $socketClient;
    }

    /**
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @param bool $strict
     */
    public function setStrict(bool $strict)
    {
        $this->strict = $strict;
    }

    /**
     * Connects to the server
     * @return bool Whether a new connection was made
     */
    public function connect()
    {
        return $this->wsClient->connect();
    }

    /**
     * Disconnects the underlying socket, and marks the client as disconnected
     * @return bool
     */
    public function disconnect()
    {
        return $this->wsClient->disconnect();
    }

    /**
     * Returns whether the client is currently connected
     * @return bool true if connected
     */
    public function isConnected()
    {
        return $this->wsClient->isConnected();
    }

    /**
     * Sends the given message and returns a response reader
     * @param Message $message
     * @throws CommunicationException
     * @return ResponseReader
     */
    public function sendMessage(Message $message): ResponseReader
    {
        $sent = $this->wsClient->sendData(json_encode([
            'id'        => $message->getId(),
            'method'    => $message->getMethod(),
            'params'    => $message->getParams()
        ]));

        if (!$sent) {
            throw new CommunicationException('Message could not be sent');
        }

        return new ResponseReader($message, $this);
    }

    /**
     * @param Message $message
     * @param int|null $timeout
     * @throws NoResponseAvailable
     * @return Response
     */
    public function sendMessageSync(Message $message, $timeout = null): Response
    {
        $responseReader = $this->sendMessage($message);
        $response = $responseReader->waitForResponse($timeout ?? $this->sendSyncDefaultTimeout);

        if (!$response) {
            throw new NoResponseAvailable('No response was sent in the given timeout');
        }

        return $response;
    }

    /**
     * Read data from CRI and store messages
     *
     * @return bool true if data were received
     * @throws CannotReadResponse
     * @throws InvalidResponse
     */
    public function readData()
    {
        $hasData = false;

        // receive data from client
        $data = $this->wsClient->receiveData();

        // and analyze them
        foreach ($data as $datum) {
            // responses come as json string
            $response = json_decode($datum, true);

            // if json not valid throw exception
            $jsonError = json_last_error();
            if ($jsonError !== JSON_ERROR_NONE) {
                if ($this->isStrict()) {
                    throw new CannotReadResponse(
                        sprintf(
                            'Response from chrome remote interface is not a valid json response. JSON error: %s',
                            $jsonError
                        )
                    );
                }
                continue;
            }

            // response must be array
            if (!is_array($response)) {
                if ($this->isStrict()) {
                    throw new CannotReadResponse('Response from chrome remote interface was not a valid array');
                }
                continue;
            }

            // id is required to identify the response
            if (!isset($response['id'])) {
                if ($this->isStrict()) {
                    throw new InvalidResponse(
                        'Response from chrome remote interface did not provide a valid message id'
                    );
                }
                continue;
            }

            // flag data received
            $hasData = true;

            // store response
            $this->responseBuffer[$response['id']] = $response;
        }

        return $hasData;
    }

    /**
     * True if a response for the given id exists
     * @param $id
     * @return bool
     */
    public function hasResponseForId($id)
    {
        return array_key_exists($id, $this->responseBuffer);
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getResponseForId($id)
    {
        if (array_key_exists($id, $this->responseBuffer)) {
            $data = $this->responseBuffer[$id];
            unset($this->responseBuffer[$id]);
            return $data;
        }

        return null;
    }
}
