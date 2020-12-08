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

use HeadlessChromium\Exception\CommunicationException;

/**
 * Used to read layout metrics of the page
 * @internal
 */
class PageLayoutMetrics extends ResponseWaiter
{
    /**
     * Returns raw page metrics data
     * @return array
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function getMetrics(): array
    {
        $response = $this->awaitResponse();
        return $response->getData()['results'];
    }

    /**
     * Returns size of scrollable area
     * @return array
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function getContentSize(): array
    {
        $response = $this->awaitResponse();
        return $response->getResultData('contentSize');
    }

    /**
     * Returns metrics relating to the layout viewport
     * @return array
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function getLayoutViewport(): array
    {
        $response = $this->awaitResponse();
        return $response->getResultData('layoutViewport');
    }

    /**
     * Returns metrics relating to the visual viewport
     * @return array
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function getVisualViewport()
    {
        $response = $this->awaitResponse();
        return $response->getResultData('visualViewport');
    }
}
