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
     *
     * @param $timeout int|null
     *
     */
    public function waitForResponse($timeout = null)
    {
        $this->response = $this->responseReader->waitForResponse($timeout);

        if (!$this->response->isSuccessful()) {
            throw new EvaluationFailed(sprintf(
                'Could not evaluate the script in the page. Message: "%s"',
                $this->response->getErrorMessage(true)
            ));
        }

        $result = $this->response->getResultData('result');
        $resultSubType = isset($result['subtype']) ? $result['subtype'] : null;

        if ($resultSubType == 'error') {
            // TODO dump javascript trace
            throw new JavascriptException('Error during javascript evaluation: ' . $result['description']);
        }

        return $this;
    }

    /**
     * Gets the value produced when the script evaluated in the page
     *
     * @param $timeout int|null
     *
     * @return mixed
     * @throws EvaluationFailed
     * @throws JavascriptException
     */
    public function getReturnValue($timeout = null)
    {
        if (!$this->response) {
            $this->waitForResponse($timeout);
        }

        return isset($this->response->getResultData('result')['value']) ? $this->response->getResultData('result')['value'] : null;
    }

    /**
     * Gets the return type of the response from the page
     *
     * @param $timeout int|null
     *
     * @return mixed
     * @throws EvaluationFailed
     */
    public function getReturnType($timeout = null)
    {
        if (!$this->response) {
            $this->waitForResponse($timeout);
        }

        return isset($this->response->getResultData('result')['type']) ? $this->response->getResultData('result')['type'] : null;
    }
}
