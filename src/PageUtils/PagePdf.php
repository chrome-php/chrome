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
    protected function getException($message)
    {
        throw new PdfFailed(
            sprintf('Cannot make a PDF. Reason : %s', $message)
        );
    }
}
