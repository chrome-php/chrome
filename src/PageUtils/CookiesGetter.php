<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Cookies\CookiesCollection;

/**
 * @internal
 */
class CookiesGetter extends ResponseWaiter
{
    /**
     * Gets the cookies collection.
     *
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     *
     * @return CookiesCollection
     */
    public function getCookies()
    {
        return new CookiesCollection(
            $this->responseReader->getResponse()->getResultData('cookies')
        );
    }
}
