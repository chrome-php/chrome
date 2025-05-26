<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom\Selector;

/**
 * @see https://developer.mozilla.org/docs/Web/API/Document/querySelector
 */
final class CssSelector implements Selector
{
    /** @var string */
    private $expressionEncoded;

    public function __construct(string $expression)
    {
        $this->expressionEncoded = \json_encode(
            $expression,
            \JSON_UNESCAPED_SLASHES
                | \JSON_UNESCAPED_UNICODE
                | \JSON_THROW_ON_ERROR
        );
    }

    public function expressionCount(): string
    {
        return \sprintf(
            'document.querySelectorAll(%s).length',
            $this->expressionEncoded
        );
    }

    public function expressionFindOne(int $position): string
    {
        return \sprintf(
            'document.querySelectorAll(%s)[%d]',
            $this->expressionEncoded,
            $position - 1
        );
    }
}
