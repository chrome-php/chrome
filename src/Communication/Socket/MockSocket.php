<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication\Socket;

/**
 * A mock adapter for unit tests
 */
class MockSocket implements SocketInterface
{

    protected $sentData = [];

    protected $receivedData = [];

    protected $isConnected = false;

    protected $shouldConnect = true;


    /**
     * @inheritdoc
     */
    public function sendData($data)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $this->sentData[] = $data;
        return true;
    }

    /**
     * resets the data stored with sendData
     */
    public function flushData()
    {
        $this->sentData = [];
    }

    /**
     * gets the data stored with sendData
     */
    public function getSentData()
    {
        return $this->sentData;
    }

    /**
     * @inheritdoc
     */
    public function receiveData(): array
    {
        $data = $this->receivedData;
        $this->receivedData = [];
        return $data;
    }

    /**
     * Add data to be returned with receiveData
     * @param $data
     */
    public function addReceivedData($data)
    {
        $this->receivedData[] = $data;
    }


    /**
     * @inheritdoc
     */
    public function connect()
    {
        $this->isConnected = $this->shouldConnect;
        return $this->isConnected;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * @inheritdoc
     */
    public function disconnect($reason = 1000)
    {
        $this->isConnected = false;
        return true;
    }
}
