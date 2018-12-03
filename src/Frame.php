<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

class Frame
{

    const LIFECYCLE_INIT = 'init';

    /**
     * @var array
     */
    protected $frameData;

    /**
     * @var array
     */
    protected $lifeCycleEvents = [];

    /**
     * @var string
     */
    protected $latestLoaderId;

    /**
     * @var string
     */
    protected $frameId;

    /**
     * @var int
     */
    protected $executionContextId;

    /**
     * Frame constructor.
     * @param array $frameData
     */
    public function __construct(array $frameData)
    {
        $this->frameData = $frameData;
        $this->latestLoaderId = $frameData['loaderId'];
        $this->frameId = $frameData['id'];
    }

    /**
     * @param array $event
     * @internal
     */
    public function onLifecycleEvent(array $params)
    {
        if (self::LIFECYCLE_INIT === $params['name']) {
            $this->lifeCycleEvents = [];
            $this->latestLoaderId = $params['loaderId'];
            $this->frameId = $params['frameId'];
        }


        $this->lifeCycleEvents[$params['name']] = $params['timestamp'];
    }

    /**
     * @return int
     */
    public function getExecutionContextId()
    {
        return $this->executionContextId;
    }

    /**
     * @param int $executionContextId
     */
    public function setExecutionContextId($executionContextId)
    {
        $this->executionContextId = $executionContextId;
    }

    /**
     * @return string
     */
    public function getFrameId()
    {
        return $this->frameId;
    }

    /**
     * @return string
     */
    public function getLatestLoaderId()
    {
        return $this->latestLoaderId;
    }

    /**
     * Gets the life cycle events of the frame with the time they occurred at.
     * @return array
     */
    public function getLifeCycle()
    {
        return $this->lifeCycleEvents;
    }
}
