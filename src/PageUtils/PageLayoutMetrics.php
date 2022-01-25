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
 * Used to read layout metrics of the page.
 *
 * @internal
 */
class PageLayoutMetrics extends ResponseWaiter
{
    /**
     * Returns raw page metrics data.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getMetrics(): array
    {
        $response = $this->awaitResponse();

        return $response->getData()['results'];
    }

    /**
     * Returns size of scrollable area.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getContentSize(): array
    {
        return $this->getResultData('contentSize');
    }

    /**
     * Returns metrics relating to the layout viewport.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getLayoutViewport(): array
    {
        return $this->getResultData('layoutViewport');
    }

    /**
     * Returns metrics relating to the visual viewport.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getVisualViewport()
    {
        return $this->getResultData('visualViewport');
    }

    /**
     * Returns real size of scrollable area.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getCssContentSize(): array
    {
        return $this->getResultData('cssContentSize') ?? $this->getContentSize();
    }

    /**
     * Returns real metrics relating to the layout viewport.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getCssLayoutViewport(): array
    {
        return $this->getResultData('cssLayoutViewport') ?? $this->getLayoutViewport();
    }

    /**
     * Returns real metrics relating to the visual viewport.
     *
     * @throws CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return array
     */
    public function getCssVisualViewport()
    {
        return $this->getResultData('cssVisualViewport') ?? $this->getVisualViewport();
    }

    /** @param 'layoutViewport'|'visualViewport'|'contentSize'|'cssLayoutViewport'|'cssVisualViewport'|'cssContentSize' $key */
    private function getResultData(string $key): array
    {
        return $this->awaitResponse()->getResultData($key);
    }
}
