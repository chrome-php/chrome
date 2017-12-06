<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication\Socket;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wrench\Client as WrenchClient;
use Wrench\Payload\Payload;

class Wrench implements SocketInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var WrenchClient
     */
    protected $client;

    /**
     * @param WrenchClient $client
     */
    public function __construct(WrenchClient $client, LoggerInterface $logger = null)
    {
        $this->client = $client;

        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @inheritdoc
     */
    public function sendData($data)
    {
        // log
        $this->logger->debug('socket: |=> sending data:' . $data);

        // send data
        return $this->client->sendData($data);
    }

    /**
     * @inheritdoc
     */
    public function receiveData(): array
    {
        $playloads = $this->client->receive();

        $data = [];

        if ($playloads) {
            foreach ($playloads as $playload) {
                /** @var $playload Payload */
                $dataString = $playload->getPayload();
                $data[] = $dataString;

                // log
                $this->logger->debug('socket: <=| receiving data:' . $dataString);
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        // log
        $this->logger->debug('socket: connecting');

        $connected = $this->client->connect();

        if ($connected) {
            // log
            $this->logger->debug('socket: ✓ connected');
        } else {
            // log
            $this->logger->debug('socket: ✗ could not connect');
        }
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return $this->client->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function disconnect($reason = 1000)
    {
        // log
        $this->logger->debug('socket: disconnecting');

        $disconnected = $this->client->disconnect($reason);

        if ($disconnected) {
            // log
            $this->logger->debug('socket: ✓ disconnected');
        } else {
            // log
            $this->logger->debug('socket: ✗ could not disconnect');
        }
        return $disconnected;
    }
}
