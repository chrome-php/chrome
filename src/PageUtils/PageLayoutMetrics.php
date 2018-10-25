<?php

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\Response;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\LayoutMetricsFailed;

/**
 * Used to read layout metrics of the page
 * @internal
 */
class PageLayoutMetrics
{

    /**
     * @var ResponseReader
     */
    protected $responseReader;

    /**
     * @var Response
     */
    protected $response;

    public function __construct(ResponseReader $responseReader)
    {
        $this->responseReader = $responseReader;
    }

    /**
     * @return $this
     * @throws LayoutMetricsFailed
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function await()
    {
        $this->response = $this->responseReader->waitForResponse();

        if (!$this->response->isSuccessful()) {
            throw new LayoutMetricsFailed('Could not retrieve layout metrics of the page.');
        }

        return $this;
    }

    /**
     * Returns raw page metrics data
     * @return array
     * @throws LayoutMetricsFailed
     */
    public function getMetrics()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response['result'];
    }

    /**
     * Returns size of scrollable area
     * @return array
     * @throws LayoutMetricsFailed
     */
    public function getContentSize()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response->getResultData('contentSize');
    }

    /**
     * Returns metrics relating to the layout viewport
     * @return array
     * @throws LayoutMetricsFailed
     */
    public function getLayoutViewport()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response->getResultData('layoutViewport');
    }

    /**
     * Returns metrics relating to the visual viewport
     * @return array
     * @throws LayoutMetricsFailed
     */
    public function getVisualViewport()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response->getResultData('visualViewport');
    }
}
