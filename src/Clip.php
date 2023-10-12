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
    protected $x;
    protected $y;
    protected $height;
    protected $width;
    protected $scale;

    /**
     * Clip constructor.
     *
     * @param int|float $x
     * @param int|float $y
     * @param int       $height
     * @param int       $width
     * @param float     $scale
     */
    public function __construct($x, $y, $width, $height, $scale = 1.0)
    {
        $this->x = (float) $x;
        $this->y = (float) $y;
        $this->height = $height;
        $this->width = $width;
        $this->scale = $scale;
    }

    /**
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return int
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
        $this->x = (float) $x;
    }

    /**
     * @param int|float $y
     */
    public function setY($y): void
    {
        $this->y = (float) $y;
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
