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

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Exception\FilesystemException;
use HeadlessChromium\Exception\PdfFailed;
use HeadlessChromium\PageUtils\PagePdf;
use InvalidArgumentException;
use stdClass;

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
            [['headerTemplate', new stdClass()]],
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
        $this->expectException(InvalidArgumentException::class);

        $this->pagePdf->setOptions([$optionName => $optionValue]);
    }

    /**
     * @dataProvider validPdfOptionsProvider
     */
    public function testValidOptions(string $optionName, $optionValue): void
    {
        self::assertInstanceOf(PagePdf::class, $this->pagePdf->setOptions([$optionName => $optionValue]));
    }

    public function testSaveToStreamReturnsDecodedContent(): void
    {
        $content = 'Test';
        $pagePdf = $this->createPagePdfWithResponse(\base64_encode($content));

        $stream = $pagePdf->saveToStream();

        self::assertIsResource($stream);
        self::assertSame($content, \stream_get_contents($stream));

        \fclose($stream);
    }

    public function testSaveToStreamUsesProvidedStream(): void
    {
        $content = 'Test';
        $pagePdf = $this->createPagePdfWithResponse(\base64_encode($content));

        $stream = \fopen('php://temp', 'r+');
        $pagePdf->saveToStream($stream);

        // the decoding filter must have been removed: later writes go through verbatim
        \fwrite($stream, 'raw');

        \rewind($stream);
        self::assertSame($content.'raw', \stream_get_contents($stream));

        \fclose($stream);
    }

    public function testSaveToStreamWritesLargeContentInChunks(): void
    {
        $content = \str_repeat('abcdef', 500000);
        $pagePdf = $this->createPagePdfWithResponse(\base64_encode($content));

        $stream = $pagePdf->saveToStream();

        self::assertSame($content, \stream_get_contents($stream));

        \fclose($stream);
    }

    public function testSaveToStreamRejectsNonWritableStream(): void
    {
        $pagePdf = $this->createPagePdfWithResponse(\base64_encode('Test'));

        $this->expectException(FilesystemException::class);

        $pagePdf->saveToStream(\fopen(__FILE__, 'r'));
    }

    public function testSaveToStreamRejectsClosedStream(): void
    {
        $pagePdf = $this->createPagePdfWithResponse(\base64_encode('Test'));

        $stream = \fopen('php://temp', 'r+');
        \fclose($stream);

        $this->expectException(FilesystemException::class);

        $pagePdf->saveToStream($stream);
    }

    public function testSaveToStreamRejectsMalformedData(): void
    {
        $pagePdf = $this->createPagePdfWithResponse('not base64!');

        $this->expectException(PdfFailed::class);

        $pagePdf->saveToStream();
    }

    public function testSaveToStreamRejectsZeroLengthWrites(): void
    {
        \stream_wrapper_register('zerowrite', ZeroWriteStreamForTests::class);

        try {
            $pagePdf = $this->createPagePdfWithResponse(\base64_encode('Test'));

            $this->expectException(FilesystemException::class);

            $pagePdf->saveToStream(\fopen('zerowrite://pdf', 'w'));
        } finally {
            \stream_wrapper_unregister('zerowrite');
        }
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

    private function createPagePdfWithResponse(string $base64Data): PagePdfForTests
    {
        $message = new Message('Page.printToPDF', []);
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);

        $mockSocket->addReceivedData(\json_encode(['id' => $message->getId(), 'result' => ['data' => $base64Data]]));

        $pagePdf = new PagePdfForTests();
        $pagePdf->setResponseReader(new ResponseReader($message, $connection));

        return $pagePdf;
    }
}
