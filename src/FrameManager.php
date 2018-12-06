<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

class FrameManager
{

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var Frame[]
     */
    protected $frames = [];

    /**
     * @var Frame
     */
    protected $mainFrame;

    /**
     * @var RequestId
     */
    protected $requestId;

    /**
     * FrameManager constructor.
     * @param Page $page
     */
    public function __construct(Page $page, array $frameTree)
    {
        $this->page = $page;

        if (isset($frameTree['frame'])) {
            // TODO parse children frames
            $this->frames[$frameTree['frame']['id']] = new Frame($frameTree['frame']);

            // associate main frame
            $this->mainFrame = $this->frames[$frameTree['frame']['id']];
        }

        // TODO listen for frame events

        // update frame on init
        $this->page->getSession()->on('method:Page.lifecycleEvent', function (array $params) {
            if (isset($this->frames[$params['frameId']])) {
                $frame = $this->frames[$params['frameId']];
                $frame->onLifecycleEvent($params);
            }
        });

        // attach context id to frame
        $this->page->getSession()->on('method:Runtime.executionContextCreated', function (array $params) {
            if (isset($params['context']['auxData']['frameId']) && $params['context']['auxData']['isDefault']) {
                if ($this->hasFrame($params['context']['auxData']['frameId'])) {
                    $frame = $this->getFrame($params['context']['auxData']['frameId']);
                    $frame->setExecutionContextId($params['context']['id']);
                }
            }
        });

        // attach request id to frame
        $this->page->getSession()->on('method:Network.requestWillBeSent', function($params) {
            if ($this->requestId === null) {
                if (isset($params['requestId'])) {
                    $this->requestId = $params['requestId'];
                }
            }
        });

        // TODO maybe implement Runtime.executionContextDestroyed and Runtime.executionContextsCleared
    }

    /**
     * Checks if the given frame exists
     * @param $frameId
     * @return bool
     */
    public function hasFrame($frameId)
    {
        return array_key_exists($frameId, $this->frames);
    }

    /**
     * Get a frame given its id
     * @param $frameId
     * @return Frame
     */
    public function getFrame($frameId)
    {
        if (!isset($this->frames[$frameId])) {
            throw new \RuntimeException(sprintf('No such frame "%s"', $frameId));
        }

        return $this->frames[$frameId];
    }

    /**
     * Gets the main frame
     * @return Frame
     */
    public function getMainFrame()
    {
        return $this->mainFrame;
    }

    /**
     * Gets the request id
     * @return RequestId
     */
    public function getrequestId()
    {
        return $this->requestId;
    }
}
