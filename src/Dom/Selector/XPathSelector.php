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

    /**
     * Quotes a string for use in an XPath expression.
     *
     * Example: new XPathSelector("//span[contains(text()," . XPathSelector::quote($string) . ")]")
     *
     * @param string $string
     *
     * @return string
     */
    public static function quote(string $string): string
    {
        if (false === \strpos($string, '"')) {
            return '"'.$string.'"';
        }
        if (false === \strpos($string, '\'')) {
            return '\''.$string.'\'';
        }
        // if the string contains both single and double quotes, construct an
        // expression that concatenates all non-double-quote substrings with
        // the quotes, e.g.:
        //   'foo'"bar" => concat("'foo'", '"bar"')
        $sb = [];
        while ('' !== $string) {
            $bytesUntilSingleQuote = \strcspn($string, '\'');
            $bytesUntilDoubleQuote = \strcspn($string, '"');
            $quoteMethod = ($bytesUntilSingleQuote > $bytesUntilDoubleQuote) ? "'" : '"';
            $bytesUntilQuote = \max($bytesUntilSingleQuote, $bytesUntilDoubleQuote);
            $sb[] = $quoteMethod.\substr($string, 0, $bytesUntilQuote).$quoteMethod;
            $string = \substr($string, $bytesUntilQuote);
        }
        $sb = \implode(',', $sb);

        return 'concat('.$sb.')';
    }
}
