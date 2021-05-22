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
     * @param int   $x
     * @param int   $y
     * @param int   $height
     * @param int   $width
     * @param float $scale
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
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param mixed $x
     */
    public function setX($x): void
    {
        $this->x = $x;
    }

    /**
     * @param mixed $y
     */
    public function setY($y): void
    {
        $this->y = $y;
    }

    /**
     * @param mixed $height
     */
    public function setHeight($height): void
    {
        $this->height = $height;
    }

    /**
     * @param mixed $width
     */
    public function setWidth($width): void
    {
        $this->width = $width;
    }

    /**
     * @param mixed $scale
     */
    public function setScale($scale): void
    {
        $this->scale = $scale;
    }
}
