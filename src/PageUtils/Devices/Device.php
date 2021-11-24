<?php

namespace HeadlessChromium\PageUtils\Devices;

abstract class Device
{
	/**
	 * @var string
	 */
	protected string $name;

	/**
	 * @var string
	 */
	protected string $userAgent;

	/**
	 * @var Viewport
	 */
	protected Viewport $viewport;

	/**
	 * @param string $name
	 * @param string $userAgent
	 * @param Viewport $viewport
	 *
	 * @return void
	 */
	public function __construct(string $name, string $userAgent, Viewport $viewport)
	{
		$this->name = $name;
		$this->userAgent = $userAgent;
		$this->viewport = $viewport;
	}

	/** 
	 * Horizontal representation of the device
	 *
	 * @return void
	*/
	public function landscape(): void
	{
		if (! $this->viewport->isLandscape()) {
			$this->viewport->rotate();
		}
	}

	/** 
	 * Vertical representation of the device
	 *
	 * @return void
	*/
	public function portrait(): void
	{
		if ($this->viewport->isLandscape()) {
			$this->viewport->rotate();
		}
	}

	/** 
	 * @return string
	*/
	public function getName(): string
	{
		return $this->name;
	}

	/** 
	 * @return string
	*/
	public function getUserAgent(): string
	{
		return $this->userAgent;
	}

	/** 
	 * @return Viewport
	*/
	public function getViewport(): Viewport
	{
		return $this->viewport;
	}
}