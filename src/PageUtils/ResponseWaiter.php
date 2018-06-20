<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\ResponseReader;

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
     */
    public function await(int $time = null)
    {
        $this->responseReader->waitForResponse($time);
    }

    /**
     * @return ResponseReader
     */
    public function getResponseReader(): ResponseReader
    {
        return $this->responseReader;
    }
}
