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
     * FrameManager constructor.
     * @param $page
     */
    public function __construct(Page $page, array $frameTree)
    {
        $this->page = $page;

        // TODO parse children frames
        $this->frames[$frameTree['frame']['id']] = new Frame($frameTree['frame']);

        // associate main frame
        $this->mainFrame = $this->frames[$frameTree['frame']['id']];

        // TODO listen for frame events

        $this->page->getSession()->on('method:Page.lifecycleEvent', function (array $params) {
            $frame = $this->frames[$params['frameId']];
            $frame->onLifecycleEvent($params);
        });
    }

    /**
     * Gets the main frame
     * @return Frame
     */
    public function getMainFrame(): Frame
    {
        return $this->mainFrame;
    }
}
