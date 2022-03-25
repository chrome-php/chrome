<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom\Selector;

interface Selector
{
    public function expressionCount(): string;

    public function expressionFindOne(int $position): string;
}
