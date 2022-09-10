<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Entity;

/**
 * HTTP response data.
 *
 * @see https://chromedevtools.github.io/devtools-protocol/tot/Network/#type-Response
 */
class NetworkResponseEntity
{
    /**
     * Response URL.
     *
     * @var string
     */
    public $url;

    /**
     * HTTP response status code.
     *
     * @var int
     */
    public $status;

    /**
     * HTTP response status text.
     *
     * @var string
     */
    public $statusText;

    /**
     * HTTP response headers.
     *
     * @var array <string,string>
     */
    public $headers;

    /**
     * Resource mimeType as determined by the browser.
     *
     * @var string
     */
    public $mimeType;

    public function __construct(
        string $url,
        int $status,
        string $statusText,
        array $headers,
        string $mimeType
    ) {
        $this->url = $url;
        $this->status = $status;
        $this->statusText = $statusText;
        $this->headers = $headers;
        $this->mimeType = $mimeType;
    }

    /**
     * Readonly trick for php < 8.1.
     *
     * @throws \InvalidArgumentException
     */
    public function __set($name, $value): void
    {
        throw new \InvalidArgumentException('Entity properties are readonly.');
    }
}
