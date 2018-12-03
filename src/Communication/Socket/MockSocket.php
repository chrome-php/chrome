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
    protected $receivedDataForNextMessage = [];

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

        if (!empty($this->receivedDataForNextMessage)) {
            $data = json_decode($data, true);

            if ($data['id']) {
                $next = array_shift($this->receivedDataForNextMessage);
                $next = json_decode($next, true);
                $next['id'] = $data['id'];
                $this->receivedData[] = json_encode($next);

                if (isset($data['method']) && $data['method'] == 'Target.sendMessageToTarget') {
                    $next['id']--;
                    $this->receivedData[] = json_encode($next);
                }
            }
        }

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
    public function receiveData()
    {
        $data = $this->receivedData;
        $this->receivedData = [];
        return $data;
    }

    /**
     * Add data to be returned with receiveData
     * @param $data
     * @param bool $forNextMessage true to set the response id automatically
     * for next message (can stack for multiple messages
     */
    public function addReceivedData($data, $forNextMessage = false)
    {
        if ($forNextMessage) {
            $this->receivedDataForNextMessage[] = $data;
        } else {
            $this->receivedData[] = $data;
        }
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
