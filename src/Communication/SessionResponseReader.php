<?php

declare(strict_types=1);

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Communication;

/**
 * Messages sends to a specific target are embedded into a parent message and 2 response are sent.
 * This object is a special instance of ResponseReader class that will read for the embedding message response
 * before reading for the real message in order to clean the response buffer.
 */
class SessionResponseReader extends ResponseReader
{
    /**
     * @var ResponseReader
     */
    protected $topResponseReader;

    public function __construct(ResponseReader $topResponseReader, Message $message)
    {
        parent::__construct($message, $topResponseReader->getConnection());
        $this->topResponseReader = $topResponseReader;
    }

    public function checkForResponse()
    {
        // make sure that the parent message is read before. This way the connection response buffer is freed from the
        // parent message.
        $topHasResponse = $this->topResponseReader->checkForResponse();

        if ($topHasResponse) {
            return parent::checkForResponse();
        }

        return false;
    }

    /**
     * @return ResponseReader
     */
    public function getTopResponseReader(): ResponseReader
    {
        return $this->topResponseReader;
    }
}
