<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Communication\Socket;

/**
 * A mock adapter for unit tests.
 */
class MockSocket implements SocketInterface
{
    protected $sentData = [];

    protected $receivedData = [];
    protected $receivedDataForNextMessage = [];

    protected $isConnected = false;

    protected $shouldConnect = true;

    /**
     * {@inheritdoc}
     */
    public function sendData($data)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $this->sentData[] = $data;

        if (!empty($this->receivedDataForNextMessage)) {
            $data = \json_decode($data, true);

            if ($data['id']) {
                $next = \array_shift($this->receivedDataForNextMessage);
                $next = \json_decode($next, true);
                $next['id'] = $data['id'];
                $this->receivedData[] = \json_encode($next);

                if (isset($data['method']) && 'Target.sendMessageToTarget' == $data['method']) {
                    --$next['id'];
                    $this->receivedData[] = \json_encode($next);
                }
            }
        }

        return true;
    }

    /**
     * resets the data stored with sendData.
     */
    public function flushData(): void
    {
        $this->sentData = [];
    }

    /**
     * gets the data stored with sendData.
     */
    public function getSentData()
    {
        return $this->sentData;
    }

    /**
     * {@inheritdoc}
     */
    public function receiveData(): array
    {
        $data = $this->receivedData;
        $this->receivedData = [];

        return $data;
    }

    /**
     * Add data to be returned with receiveData.
     *
     * @param bool $forNextMessage true to set the response id automatically
     *                             for next message (can stack for multiple messages
     */
    public function addReceivedData($data, $forNextMessage = false): void
    {
        if ($forNextMessage) {
            $this->receivedDataForNextMessage[] = $data;
        } else {
            $this->receivedData[] = $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $this->isConnected = $this->shouldConnect;

        return $this->isConnected;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect($reason = 1000)
    {
        $this->isConnected = false;

        return true;
    }
}
