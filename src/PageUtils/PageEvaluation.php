<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\Response;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\EvaluationFailed;
use HeadlessChromium\Exception\JavascriptException;
use HeadlessChromium\Page;

/**
 * Used to read data from page evaluation response
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
     * @param ResponseReader $responseReader
     * @internal
     */
    public function __construct(ResponseReader $responseReader, $pageLoaderId, Page $page)
    {
        $this->responseReader = $responseReader;
        $this->pageLoaderId = $pageLoaderId;
        $this->page = $page;
    }

    /**
     * If the script requested a page reload this method will help to wait for the page to be fully reloaded
     */
    public function waitForPageReload($eventName = Page::LOAD, $timeout = 30000)
    {
        $this->page->waitForReload($eventName, $timeout, $this->pageLoaderId);
    }

    /**
     * Wait for the script to evaluate and to return a valid response
     */
    public function waitForResponse()
    {
        $this->response = $this->responseReader->waitForResponse();

        if (!$this->response->isSuccessful()) {
            throw new EvaluationFailed('Could not evaluate the script in the page.');
        }

        $result = $this->response->getResultData('result');

        $resultSubType = $result['subtype'] ?? null;

        if ($resultSubType == 'error') {
            // TODO dump javascript trace
            throw new JavascriptException('Error during javascript evaluation: ' . $result['description']);
        }

        return $this;
    }

    /**
     * Gets the value produced when the script evaluated in the page
     * @return mixed
     * @throws EvaluationFailed
     */
    public function getReturnValue()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response->getResultData('result')['value'] ?? null;
    }

    /**
     * Gets the return type of the response from the page
     * @return mixed
     * @throws EvaluationFailed
     */
    public function getReturnType()
    {
        if (!$this->response) {
            $this->waitForResponse();
        }

        return $this->response->getResultData('result')['type'] ?? null;
    }
}
