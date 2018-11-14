<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Exception\PdfFailed;

class PagePdf extends AbstractBinaryInput
{
    /**
     * @inheritdoc
     * @internal
     */
    protected function getException(string $message): \Exception
    {
        new PdfFailed(
            sprintf('Cannot make a screenshot. Reason : %s', $message)
        );
    }
}
