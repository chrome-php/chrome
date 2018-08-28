<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;

class ResponseWaiter
{

    /**
     * @var ResponseReader
     */
    protected $responseReader;

    /**
     * @param ResponseReader $responseReader
     */
    public function __construct(ResponseReader $responseReader)
    {
        $this->responseReader = $responseReader;
    }

    /**
     * @param $time
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     *
     * @return $this
     */
    public function await(int $time = null)
    {
        $this->responseReader->waitForResponse($time);

        $response = $this->responseReader->getResponse();

        if (!$response->isSuccessful()) {
            throw new ResponseHasError($response->getErrorMessage(true));
        }

        return $this;
    }

    /**
     * @return ResponseReader
     */
    public function getResponseReader(): ResponseReader
    {
        return $this->responseReader;
    }
}
