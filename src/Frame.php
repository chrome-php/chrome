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

class Frame
{
    public const LIFECYCLE_INIT = 'init';

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
     *
     * @param array $frameData
     */
    public function __construct(array $frameData)
    {
        $this->frameData = $frameData;
        $this->latestLoaderId = $frameData['loaderId'];
        $this->frameId = $frameData['id'];
    }

    /**
     * @internal
     */
    public function onLifecycleEvent(array $params): void
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
    public function getExecutionContextId(): int
    {
        return $this->executionContextId;
    }

    /**
     * @param int $executionContextId
     */
    public function setExecutionContextId(int $executionContextId): void
    {
        $this->executionContextId = $executionContextId;
    }

    /**
     * @return string
     */
    public function getFrameId(): string
    {
        return $this->frameId;
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
     *
     * @return array
     */
    public function getLifeCycle(): array
    {
        return $this->lifeCycleEvents;
    }
}
