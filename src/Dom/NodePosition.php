<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

class NodePosition
{
    /**
     * @var float
     */
    private $x;

    /**
     * @var float
     */
    private $y;

    /**
     * @var float
     */
    private $width;

    /**
     * @var float
     */
    private $height;

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

        $this->x = (float) $leftTopX;
        $this->y = (float) $leftTopY;

        $this->height = (float) ($leftBottomY - $leftTopY);
        $this->width = (float) ($rightBottomX - $leftBottomX);
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getCenterX(): float
    {
        return $this->x + ($this->width / 2);
    }

    public function getCenterY(): float
    {
        return $this->y + ($this->height / 2);
    }
}
