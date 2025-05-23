<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom\Selector;

/**
 * @see https://developer.mozilla.org/docs/Web/API/Document/querySelector
 */
final class CssSelector implements Selector
{
    /** @var string */
    private $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function expressionCount(): string
    {
        $encodedExpr = \json_encode(
            $this->expression,
            \JSON_UNESCAPED_SLASHES
                | \JSON_UNESCAPED_UNICODE
                | \JSON_THROW_ON_ERROR
        );

        return \sprintf(
            'document.querySelectorAll(%s).length',
            $encodedExpr
        );
    }

    public function expressionFindOne(int $position): string
    {
        $encodedExpr = \json_encode(
            $this->expression,
            \JSON_UNESCAPED_SLASHES
                | \JSON_UNESCAPED_UNICODE
                | \JSON_THROW_ON_ERROR
        );

        return \sprintf(
            'document.querySelectorAll(%s)[%d]',
            $encodedExpr,
            $position - 1
        );
    }
}
