<?php

namespace HeadlessChromium\PageUtils\Devices;

/** @package HeadlessChromium\PageUtils\Devices */
abstract class Resolution
{
	/**
	 * @var int
	 */
	protected int $width;

	/**
	 * @var int
	 */
	protected int $height;

	/**
	 * @var float
	 */
	protected float $scaleFactor;

	/**
	 * @param int $width 
	 * @param int $height
	 * $param float $scaleFactor
	 *  
	 * @return void 
	 */
	public function __construct(int $width, int $height, float $scaleFactor)
	{
		$this->width = $width;
		$this->height = $height;
		$this->scaleFactor = $scaleFactor;
	}

	/** 
	 * @return int
	*/
	public function getWidth(): int
	{
		return $this->width;
	}

	/** 
	 * @return int
	*/
	public function getHeight(): int
	{
		return $this->height;
	}

	/** 
	 * @return float
	*/
	public function getScaleFactor(): float
	{
		return $this->scaleFactor;
	}

	/** 
	 * @return void
	*/
	public function rotate(): void
	{
		$this->height = $this->width;
		$this->width = $this->height;
	}
}