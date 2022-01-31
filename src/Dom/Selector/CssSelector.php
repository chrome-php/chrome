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
        return \sprintf('document.querySelectorAll("%s").length', $this->expression);
    }

    public function expressionFindOne(int $position): string
    {
        return \sprintf('document.querySelectorAll("%s")[%d]', $this->expression, $position - 1);
    }
}
