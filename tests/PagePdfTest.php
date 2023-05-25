<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\PageUtils\PagePdf;

/**
 * @covers \HeadlessChromium\PagePdf
 */
class PagePdfTest extends BaseTestCase
{
    private const TYPES_STRING = [
        'string',
        '',
    ];

    private const TYPES_NUMERIC = [
        1,
        1.1,
    ];

    private const TYPES_BOOLEAN = [
        true,
        false,
    ];

    private PagePdfForTests $pagePdf;

    /**
     * @before
     */
    public function createEmptyPagePdf(): void
    {
        $this->pagePdf = new PagePdfForTests();
    }

    public static function invalidPdfOptionsProvider(): array
    {
        return \array_merge(
            self::getOptionsDataset('landscape', self::TYPES_STRING),
            self::getOptionsDataset('headerTemplate', self::TYPES_NUMERIC),
            self::getOptionsDataset('scale', self::TYPES_STRING),
            [['headerTemplate', new \stdClass()]],
            [['footerTemplate', []]],
            [['unknown_field',  1]],
        );
    }

    public static function validPdfOptionsProvider(): array
    {
        return \array_merge(
            self::getOptionsDataset('landscape', self::TYPES_BOOLEAN),
            self::getOptionsDataset('printBackground', self::TYPES_BOOLEAN),
            self::getOptionsDataset('displayHeaderFooter', self::TYPES_BOOLEAN),
            self::getOptionsDataset('headerTemplate', self::TYPES_STRING),
            self::getOptionsDataset('footerTemplate', self::TYPES_STRING),
            self::getOptionsDataset('paperWidth', self::TYPES_NUMERIC),
            self::getOptionsDataset('paperHeight', self::TYPES_NUMERIC),
            self::getOptionsDataset('marginTop', self::TYPES_NUMERIC),
            self::getOptionsDataset('marginBottom', self::TYPES_NUMERIC),
            self::getOptionsDataset('marginLeft', self::TYPES_NUMERIC),
            self::getOptionsDataset('marginRight', self::TYPES_NUMERIC),
            self::getOptionsDataset('pageRanges', self::TYPES_STRING),
            self::getOptionsDataset('ignoreInvalidPageRanges', self::TYPES_BOOLEAN),
            self::getOptionsDataset('preferCSSPageSize', self::TYPES_BOOLEAN),
            self::getOptionsDataset('scale', self::TYPES_NUMERIC),
        );
    }

    /**
     * @dataProvider invalidPdfOptionsProvider
     */
    public function testInvalidOptions(string $optionName, $optionValue): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->pagePdf->setOptions([$optionName => $optionValue]);
    }

    /**
     * @dataProvider validPdfOptionsProvider
     */
    public function testValidOptions(string $optionName, $optionValue): void
    {
        self::assertInstanceOf(PagePdf::class, $this->pagePdf->setOptions([$optionName => $optionValue]));
    }

    private static function getOptionsDataset(string $optionName, array $optionValues): array
    {
        return \array_reduce(
            $optionValues,
            function ($carry, $value) use ($optionName) {
                $carry[] = [$optionName, $value];

                return $carry;
            },
            []
        );
    }
}
