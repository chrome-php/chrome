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
 * A simplified interface to wrap a socket client.
 */
interface SocketInterface
{
    /**
     * Sends data to the socket.
     *
     * @return bool whether the data were sent
     */
    public function sendData($data);

    /**
     * Receives data sent by the server.
     *
     * @return array Payload received since the last call to receive()
     */
    public function receiveData(): array;

    /**
     * Connect to the server.
     *
     * @return bool Whether a new connection was made
     */
    public function connect();

    /**
     * Whether the client is currently connected.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Disconnects the underlying socket, and marks the client as disconnected.
     *
     * @param int $reason see http://tools.ietf.org/html/rfc6455#section-7.4
     *
     * @return bool
     */
    public function disconnect($reason = 1000);
}
