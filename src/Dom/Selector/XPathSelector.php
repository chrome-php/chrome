<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom\Selector;

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/XPath/Introduction_to_using_XPath_in_JavaScript
 */
final class XPathSelector implements Selector
{
    /** @var string */
    private $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function expressionCount(): string
    {
        return \sprintf(
            'document.evaluate("%s", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null).snapshotLength',
            \addslashes($this->expression)
        );
    }

    public function expressionFindOne(int $position): string
    {
        return \sprintf(
            'document.evaluate("%s[%d]", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue',
            \addslashes($this->expression),
            $position
        );
    }
}
