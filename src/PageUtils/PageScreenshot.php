<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Exception\ScreenshotFailed;

class PageScreenshot extends AbstractBinaryInput
{
    /**
     * @inheritdoc
     * @internal
     */
    protected function getException(string $message): \Exception
    {
        new ScreenshotFailed(
            sprintf('Cannot make a screenshot. Reason : %s', $message)
        );
    }
}
