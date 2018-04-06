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
     * Frame constructor.
     * @param array $frameData
     */
    public function __construct(array $frameData)
    {
        $this->frameData = $frameData;
        $this->latestLoaderId = $frameData['loaderId'];
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
        }


        $this->lifeCycleEvents[$params['name']] = $params['timestamp'];
    }

    /**
     * @return string
     */
    public function getLatestLoaderId(): string
    {
        return $this->latestLoaderId;
    }

    /**
     * Gets the life cycle events of the frame with the time they occurred at.
     * @return array
     */
    public function getLifeCycle(): array
    {
        return $this->lifeCycleEvents;
    }
}
