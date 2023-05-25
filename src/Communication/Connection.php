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
use HeadlessChromium\Communication\Socket\SocketInterface;
use HeadlessChromium\Communication\Socket\Wrench;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Exception\TargetDestroyed;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wrench\Client as WrenchBaseClient;

class Connection extends EventEmitter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const EVENT_TARGET_CREATED = 'method:Target.targetCreated';
    public const EVENT_TARGET_INFO_CHANGED = 'method:Target.targetInfoChanged';
    public const EVENT_TARGET_DESTROYED = 'method:Target.targetDestroyed';

    /**
     * When strict mode is enabled communication error will result in exceptions.
     *
     * @var bool
     */
    protected $strict = true;

    /**
     * time in ms to wait between each message to be sent
     * That helps to see what is happening when debugging.
     *
     * @var int
     */
    protected $delay;

    /**
     * time in ms when the previous message was sent. Used to know how long to wait for before send next message
     * (only when $delay is set).
     *
     * @var int
     */
    private $lastMessageSentTime;

    /**
     * @var SocketInterface
     */
    protected $wsClient;

    /**
     * List of response sent from the remote host and that are waiting to be read.
     *
     * @var array
     */
    protected $responseBuffer = [];

    /**
     * Default timeout for send sync in ms.
     *
     * @var int
     */
    protected $sendSyncDefaultTimeout;

    /**
     * @var Session[]
     */
    protected $sessions = [];

    /**
     * @var array array of data received and waiting to be read
     */
    protected $receivedData = [];

    /**
     * @var array<string, string>
     */
    protected $httpHeaders = [];

    /**
     * CommunicationChannel constructor.
     *
     * @param SocketInterface|string $socketClient
     * @param int|null               $sendSyncDefaultTimeout
     */
    public function __construct($socketClient, LoggerInterface $logger = null, int $sendSyncDefaultTimeout = null)
    {
        // set or create logger
        $this->setLogger($logger ?? new NullLogger());

        // set timeout
        $this->sendSyncDefaultTimeout = $sendSyncDefaultTimeout ?? 5000;

        // create socket client
        if (\is_string($socketClient)) {
            $socketClient = new Wrench(new WrenchBaseClient($socketClient, 'http://127.0.0.1'), $this->logger);
        } elseif (!\is_object($socketClient) && !$socketClient instanceof SocketInterface) {
            throw new \InvalidArgumentException('$socketClient param should be either a SockInterface instance or a web socket uri string');
        }

        $this->wsClient = $socketClient;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the delay to apply everytime before data are sent.
     *
     * @param int $delay
     */
    public function setConnectionDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return void
     */
    public function setConnectionHttpHeaders(array $headers): void
    {
        $this->httpHeaders = $headers;
    }

    /**
     * @return array<string, string>
     */
    public function getConnectionHttpHeaders(): array
    {
        return $this->httpHeaders;
    }

    /**
     * Gets the default timeout used when sending a message synchronously.
     *
     * @return int
     */
    public function getSendSyncDefaultTimeout(): int
    {
        return $this->sendSyncDefaultTimeout;
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
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * Connects to the server.
     *
     * @return bool Whether a new connection was made
     */
    public function connect()
    {
        return $this->wsClient->connect();
    }

    /**
     * Disconnects the underlying socket, and marks the client as disconnected.
     *
     * @return bool
     */
    public function disconnect()
    {
        return $this->wsClient->disconnect();
    }

    /**
     * Returns whether the client is currently connected.
     *
     * @return bool true if connected
     */
    public function isConnected()
    {
        return $this->wsClient->isConnected();
    }

    /**
     * Wait before sending next message.
     */
    private function waitForDelay(): void
    {
        if ($this->lastMessageSentTime) {
            $currentTime = (int) (\hrtime(true) / 1000 / 1000);
            // if not enough time was spent until last message was sent, wait
            if ($this->lastMessageSentTime + $this->delay > $currentTime) {
                $timeToWait = ($this->lastMessageSentTime + $this->delay) - $currentTime;
                \usleep($timeToWait * 1000);
            }
        }

        $this->lastMessageSentTime = (int) (\hrtime(true) / 1000 / 1000);
    }

    /**
     * Sends the given message and returns a response reader.
     *
     * @param Message $message
     *
     * @throws CommunicationException
     *
     * @return ResponseReader
     */
    public function sendMessage(Message $message): ResponseReader
    {
        // if delay enabled wait before sending message
        if ($this->delay > 0) {
            $this->waitForDelay();
        }

        $sent = $this->wsClient->sendData((string) $message);

        if (!$sent) {
            $message = 'Message could not be sent.';

            if (!$this->isConnected()) {
                $message .= ' Reason: the connection is closed.';
            } else {
                $message .= ' Reason: unknown.';
            }

            throw new CommunicationException($message);
        }

        return new ResponseReader($message, $this);
    }

    /**
     * @param Message  $message
     * @param int|null $timeout
     *
     * @throws OperationTimedOut
     *
     * @return Response
     */
    public function sendMessageSync(Message $message, int $timeout = null): Response
    {
        $responseReader = $this->sendMessage($message);
        $response = $responseReader->waitForResponse($timeout);

        return $response;
    }

    /**
     * Create a session for the given target id.
     *
     * @param string  $targetId
     * @param ?string $sessionId
     *
     * @return Session
     */
    public function createSession($targetId, $sessionId = null): Session
    {
        if (null === $sessionId) {
            $response = $this->sendMessageSync(
                new Message('Target.attachToTarget', ['targetId' => $targetId, 'flatten' => true])
            );
            if (empty($response['result'])) {
                throw new TargetDestroyed('The target was destroyed.');
            }
            $sessionId = $response['result']['sessionId'];
        }
        $session = new Session($targetId, $sessionId, $this);

        $this->sessions[$sessionId] = $session;

        $session->on('destroyed', function () use ($sessionId): void {
            $this->logger->debug('✘ session('.$sessionId.') was destroyed and unreferenced.');
            unset($this->sessions[$sessionId]);
        });

        return $session;
    }

    /**
     * Receive and stack data from the socket.
     */
    private function receiveData(): void
    {
        $this->receivedData = \array_merge($this->receivedData, $this->wsClient->receiveData());
    }

    /**
     * Read data from CRI and store messages.
     *
     * @throws CannotReadResponse
     * @throws InvalidResponse
     *
     * @return bool true if data were received
     */
    public function readData()
    {
        $hasData = false;

        while ($this->readLine()) {
            $hasData = true;
        }

        return $hasData;
    }

    public function readLine()
    {
        // if buffer empty, then read from input
        if (empty($this->receivedData)) {
            $this->receiveData();
        }

        // dispatch first line of buffer
        $datum = \array_shift($this->receivedData);
        if ($datum) {
            return $this->dispatchMessage($datum);
        }

        return false;
    }

    /**
     * Dispatches the message and either stores the response or emits an event.
     *
     * @throws InvalidResponse
     *
     * @return bool
     *
     * @internal
     */
    private function dispatchMessage(string $message, Session $session = null)
    {
        // responses come as json string
        $response = \json_decode($message, true);

        // if json not valid throw exception
        $jsonError = \json_last_error();
        if (\JSON_ERROR_NONE !== $jsonError) {
            if ($this->isStrict()) {
                throw new CannotReadResponse(\sprintf('Response from chrome remote interface is not a valid json response. JSON error: %s', $jsonError));
            }

            return false;
        }

        // response must be array
        if (!\is_array($response)) {
            if ($this->isStrict()) {
                throw new CannotReadResponse('Response from chrome remote interface was not a valid array');
            }

            return false;
        }

        // id is required to identify the response
        if (!isset($response['id'])) {
            if (isset($response['method'])) {
                if ('Target.receivedMessageFromTarget' == $response['method']) {
                    $session = $this->sessions[$response['params']['sessionId']];

                    return $this->dispatchMessage($response['params']['message'], $session);
                } else {
                    if (!$session && isset($response['sessionId'])) {
                        $session = $this->sessions[$response['sessionId']] ?? null;
                    }
                    if ($session) {
                        $this->logger->debug(
                            'session('.$session->getSessionId().'): ⇶ dispatching method:'.$response['method']
                        );
                        $session->emit('method:'.$response['method'], [$response['params']]);
                    } else {
                        $this->logger->debug('connection: ⇶ dispatching method:'.$response['method']);
                        $this->emit('method:'.$response['method'], [$response['params']]);
                    }
                }

                return false;
            }

            if ($this->isStrict()) {
                throw new InvalidResponse('Response from chrome remote interface did not provide a valid message id');
            }

            return false;
        }

        // store response
        $this->responseBuffer[$response['id']] = $response;

        return true;
    }

    /**
     * True if a response for the given id exists.
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasResponseForId($id)
    {
        return \array_key_exists($id, $this->responseBuffer);
    }

    /**
     * @param string $id
     *
     * @return array|null
     */
    public function getResponseForId($id)
    {
        if (\array_key_exists($id, $this->responseBuffer)) {
            $data = $this->responseBuffer[$id];
            unset($this->responseBuffer[$id]);

            return $data;
        }

        return null;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function isSessionDestroyed($sessionId)
    {
        return !isset($this->sessions[$sessionId]);
    }
}
