<?php

namespace HeadlessChromium\PageUtils\Devices;

/** @package HeadlessChromium\PageUtils\Devices */
abstract class Viewport
{
	/**
	 * @var Resolution
	 */
	protected Resolution $resolution;

	/**
	 * @var bool
	 */
	protected bool $isMobile;

	/**
	 * @var bool
	 */
	protected bool $hasTouch;

	/**
	 * @var bool
	 */
	protected bool $isLandscape;

	/**
	 * @param Resolution $resolution 
	 * @param bool $isMobile 
	 * @param bool $hasTouch 
	 * @param bool $isLandscape 
	 * 
	 * @return void 
	 */
	public function __construct(
		Resolution $resolution,  
		bool $isMobile, 
		bool $hasTouch, 
		bool $isLandscape
	) {
		$this->resolution = $resolution;
		$this->isMobile = $isMobile;
		$this->hasTouch = $hasTouch;
		$this->isLandscape = $isLandscape;
	}

	/** 
	 * @return Resolution
	*/
	public function getResolution(): Resolution 
	{
		return $this->resolution;
	}

	/** 
	 * @return bool
	*/
	public function isMobile(): bool
	{
		return $this->isMobile;
	}

	/** 
	 * @return bool
	*/
	public function hasTouch(): bool
	{
		return $this->hasTouch;
	}

	/** 
	 * @return bool
	*/
	public function isLandscape(bool $switch = false): bool
	{
		return $this->isLandscape = $switch;
	}
}