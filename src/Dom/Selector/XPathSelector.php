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
        return 'document.evaluate('.\json_encode($this->expression, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE).', document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null).snapshotLength';
    }

    public function expressionFindOne(int $position): string
    {
        return 'document.evaluate('.\json_encode($this->expression."[{$position}]", \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE).', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue';
    }
}
