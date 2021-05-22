<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        $this->page->getSession()->on('method:Page.lifecycleEvent', function (array $params): void {
            if (isset($this->frames[$params['frameId']])) {
                $frame = $this->frames[$params['frameId']];
                $frame->onLifecycleEvent($params);
            }
        });

        // attach context id to frame
        $this->page->getSession()->on('method:Runtime.executionContextCreated', function (array $params): void {
            if (isset($params['context']['auxData']['frameId']) && $params['context']['auxData']['isDefault']) {
                if ($this->hasFrame($params['context']['auxData']['frameId'])) {
                    $frame = $this->getFrame($params['context']['auxData']['frameId']);
                    $frame->setExecutionContextId($params['context']['id']);
                }
            }
        });

        // TODO maybe implement Runtime.executionContextDestroyed and Runtime.executionContextsCleared
    }

    /**
     * Checks if the given frame exists.
     *
     * @param string $frameId
     *
     * @return bool
     */
    public function hasFrame($frameId): bool
    {
        return \array_key_exists($frameId, $this->frames);
    }

    /**
     * Get a frame given its id.
     *
     * @param string $frameId
     *
     * @return Frame
     */
    public function getFrame($frameId): Frame
    {
        if (!isset($this->frames[$frameId])) {
            throw new \RuntimeException(\sprintf('No such frame "%s"', $frameId));
        }

        return $this->frames[$frameId];
    }

    /**
     * Gets the main frame.
     *
     * @return Frame
     */
    public function getMainFrame(): Frame
    {
        return $this->mainFrame;
    }
}
