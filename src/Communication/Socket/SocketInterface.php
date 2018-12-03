<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Communication\Socket;

/**
 * A simplified interface to wrap a socket client
 */
interface SocketInterface
{

    /**
     * Sends data to the socket
     *
     * @return bool whether the data were sent
     */
    public function sendData($data);

    /**
     * Receives data sent by the server
     *
     * @return array Payload received since the last call to receive()
     */
    public function receiveData();

    /**
     * Connect to the server
     *
     * @return bool Whether a new connection was made
     */
    public function connect();

    /**
     * Whether the client is currently connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Disconnects the underlying socket, and marks the client as disconnected
     *
     * @param int $reason see http://tools.ietf.org/html/rfc6455#section-7.4
     * @return boolean
     */
    public function disconnect($reason = 1000);
}
