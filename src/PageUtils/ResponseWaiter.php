<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\Response;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;

class ResponseWaiter
{
    /**
     * @var ResponseReader
     */
    protected $responseReader;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @param ResponseReader $responseReader
     */
    public function __construct(ResponseReader $responseReader)
    {
        $this->responseReader = $responseReader;
    }

    /**
     * Chainable wait for response.
     *
     * @param int $time
     *
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     *
     * @return $this
     */
    public function await(int $time = null)
    {
        $this->response = $this->responseReader->waitForResponse($time);

        if (!$this->response->isSuccessful()) {
            throw new ResponseHasError($this->response->getErrorMessage(true));
        }

        return $this;
    }

    /**
     * Waits for response and return it.
     *
     * @param int|null $time
     *
     * @throws ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return Response
     */
    protected function awaitResponse(int $time = null): Response
    {
        if (!$this->response) {
            $this->await($time);
        }

        return $this->response;
    }

    /**
     * @return ResponseReader
     */
    public function getResponseReader(): ResponseReader
    {
        return $this->responseReader;
    }
}
