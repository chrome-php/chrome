<?php

namespace HeadlessChromium\PageUtils;

/**
 * @internal
 */
class ContentGetter extends ResponseWaiter
{
    public function getContent()
    {
        if ($this->responseReader->getResponse()->getResultData('content') !== null) {
            return $this->responseReader->getResponse()->getResultData('content');
        } else {
            return null;
        }
    }
}