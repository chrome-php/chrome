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

class Clip
{
    /** @var int|float */
    protected $x;
    /** @var int|float */
    protected $y;
    /** @var int|float */
    protected $height;
    /** @var int|float */
    protected $width;
    /** @var float */
    protected $scale;

    /**
     * @param int|float $x
     * @param int|float $y
     * @param int|float $height
     * @param int|float $width
     * @param float     $scale
     */
    public function __construct($x, $y, $width, $height, $scale = 1.0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->height = $height;
        $this->width = $width;
        $this->scale = $scale;
    }

    /**
     * @return int|float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int|float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int|float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return int|float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param int|float $x
     */
    public function setX($x): void
    {
        $this->x = $x;
    }

    /**
     * @param int|float $y
     */
    public function setY($y): void
    {
        $this->y = $y;
    }

    /**
     * @param int $height
     */
    public function setHeight($height): void
    {
        $this->height = $height;
    }

    /**
     * @param int $width
     */
    public function setWidth($width): void
    {
        $this->width = $width;
    }

    /**
     * @param float $scale
     */
    public function setScale($scale): void
    {
        $this->scale = $scale;
    }
}
