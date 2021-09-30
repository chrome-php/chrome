<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

class NodePosition
{

    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * NodePosition constructor.
     */
    public function __construct(array $points)
    {
        $leftTopX = $points[0];
        $leftTopY = $points[1];
        $rightTopX = $points[2];
        $rightTopY = $points[3];
        $rightBottomX = $points[4];
        $rightBottomY = $points[5];
        $leftBottomX = $points[6];
        $leftBottomY = $points[7];

        $this->x = $leftTopX;
        $this->y = $leftTopY;

        $this->height = $leftBottomY - $leftTopY;
        $this->width = $rightBottomX - $leftBottomX;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return (int)$this->x;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return (int)$this->y;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return (int)$this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return (int)$this->height;
    }

    /**
     * @return int
     */
    public function getCenterX()
    {
        return (int)($this->x + ($this->width / 2));
    }

    /**
     * @return int
     */
    public function getCenterY()
    {
        return (int)($this->y + ($this->height / 2));
    }
}
