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
     * @author  Robert Rossney ( https://stackoverflow.com/users/19403/robert-rossney )
     *
     * @param string $value
     *
     * @return string
     */
    public static function quote(string $value): string
    {
        if (false === \strpos($value, '"')) {
            return '"'.$value.'"';
        }
        if (false === \strpos($value, '\'')) {
            return '\''.$value.'\'';
        }
        // if the value contains both single and double quotes, construct an
        // expression that concatenates all non-double-quote substrings with
        // the quotes, e.g.:
        //    concat("'foo'", '"', "bar")
        $sb = 'concat(';
        $substrings = \explode('"', $value);
        for ($i = 0; $i < \count($substrings); ++$i) {
            $needComma = ($i > 0);
            if ('' !== $substrings[$i]) {
                if ($i > 0) {
                    $sb .= ', ';
                }
                $sb .= '"'.$substrings[$i].'"';
                $needComma = true;
            }
            if ($i < (\count($substrings) - 1)) {
                if ($needComma) {
                    $sb .= ', ';
                }
                $sb .= "'\"'";
            }
        }
        $sb .= ')';

        return $sb;
    }
}
