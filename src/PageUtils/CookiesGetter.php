<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Cookies\CookiesCollection;

/**
 * @internal
 */
class CookiesGetter extends ResponseWaiter
{
    /**
     * Gets the cookies collection
     * @return CookiesCollection
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function getCookies()
    {
        if ($this->responseReader->getResponse()->getResultData('cookies') !== null) {
            return new CookiesCollection($this->responseReader->getResponse()->getResultData('cookies'));
        } else {
            return new CookiesCollection(null());
        }
    }
}
