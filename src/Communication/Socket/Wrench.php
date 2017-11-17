<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication\Socket;

use Wrench\Client as WrenchClient;
use Wrench\Payload\Payload;

class Wrench implements SocketInterface
{

    /**
     * @var WrenchClient
     */
    protected $client;

    /**
     * @param WrenchClient $client
     */
    public function __construct(WrenchClient $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function sendData($data)
    {
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
                $data[] = $playload->getPayload();
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        return $this->client->connect();
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
        return $this->client->disconnect($reason);
    }
}
