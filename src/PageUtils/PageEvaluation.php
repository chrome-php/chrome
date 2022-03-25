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
use HeadlessChromium\Exception\EvaluationFailed;
use HeadlessChromium\Exception\JavascriptException;
use HeadlessChromium\Page;

/**
 * Used to read data from page evaluation response.
 *
 * @internal
 */
class PageEvaluation
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
     * @var string
     */
    protected $pageLoaderId;

    /**
     * @var Page
     */
    protected $page;

    /**
     * PageEvaluation constructor.
     *
     * @param ResponseReader $responseReader\
     * @param string         $pageLoaderId
     *
     * @internal
     */
    public function __construct(ResponseReader $responseReader, $pageLoaderId, Page $page)
    {
        $this->responseReader = $responseReader;
        $this->pageLoaderId = $pageLoaderId;
        $this->page = $page;
    }

    /**
     * If the script requested a page reload this method will help to wait for the page to be fully reloaded.
     */
    public function waitForPageReload($eventName = Page::LOAD, $timeout = 30000): void
    {
        $this->page->waitForReload($eventName, $timeout, $this->pageLoaderId);
    }

    /**
     * Wait for the script to evaluate and to return a valid response.
     *
     * @param int|null $timeout
     */
    public function waitForResponse(int $timeout = null)
    {
        $this->response = $this->responseReader->waitForResponse($timeout);

        if (!$this->response->isSuccessful()) {
            throw new EvaluationFailed(\sprintf('Could not evaluate the script in the page. Message: "%s"', $this->response->getErrorMessage(true)));
        }

        $result = $this->response->getResultData('result');

        $resultSubType = $result['subtype'] ?? null;

        if ('error' == $resultSubType) {
            // TODO dump javascript trace
            throw new JavascriptException('Error during javascript evaluation: '.$result['description']);
        }

        return $this;
    }

    /**
     * Gets the value produced when the script evaluated in the page.
     *
     * @param int|null $timeout
     *
     * @throws EvaluationFailed
     * @throws JavascriptException
     *
     * @return mixed
     */
    public function getReturnValue(int $timeout = null)
    {
        if (!$this->response) {
            $this->waitForResponse($timeout);
        }

        return $this->response->getResultData('result')['value'] ?? null;
    }

    /**
     * Gets the return type of the response from the page.
     *
     * @param int|null $timeout
     *
     * @throws EvaluationFailed
     *
     * @return mixed
     */
    public function getReturnType(int $timeout = null)
    {
        if (!$this->response) {
            $this->waitForResponse($timeout);
        }

        return $this->response->getResultData('result')['type'] ?? null;
    }
}
