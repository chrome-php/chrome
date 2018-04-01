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
     * Frame constructor.
     * @param array $frameData
     */
    public function __construct(array $frameData)
    {
        $this->frameData = $frameData;
    }

    /**
     * @param array $event
     * @internal
     */
    public function onLifecycleEvent(array $params)
    {
        if (self::LIFECYCLE_INIT === $params['name']) {
            $this->lifeCycleEvents = [];
        }

        $this->lifeCycleEvents[$params['name']] = $params['timestamp'];
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
