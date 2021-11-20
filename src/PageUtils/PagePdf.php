<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\PdfFailed;
use HeadlessChromium\Page;

class PagePdf extends AbstractBinaryInput
{
    private const TYPE_NUMERIC = 1;
    private const TYPE_STRING = 2;
    private const TYPE_BOOLEAN = 3;

    /**
     * @see https://chromedevtools.github.io/devtools-protocol/tot/Page/#method-printToPDF
     */
    private const OPTIONS = [
        'landscape' => self::TYPE_BOOLEAN,
        'printBackground' => self::TYPE_BOOLEAN,
        'displayHeaderFooter' => self::TYPE_BOOLEAN,
        'headerTemplate' => self::TYPE_STRING,
        'footerTemplate' => self::TYPE_STRING,
        'paperWidth' => self::TYPE_NUMERIC,
        'paperHeight' => self::TYPE_NUMERIC,
        'marginTop' => self::TYPE_NUMERIC,
        'marginBottom' => self::TYPE_NUMERIC,
        'marginLeft' => self::TYPE_NUMERIC,
        'marginRight' => self::TYPE_NUMERIC,
        'pageRanges' => self::TYPE_STRING,
        'ignoreInvalidPageRanges' => self::TYPE_BOOLEAN,
        'preferCSSPageSize' => self::TYPE_BOOLEAN,
        'scale' => self::TYPE_NUMERIC,
    ];

    /**
     * @var Page
     */
    private $page;

    private $options = [];

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     */
    public function __construct(Page $page, array $options = [])
    {
        $this->page = $page;

        $this->setOptions($options)->print();
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     */
    public function print(): self
    {
        $responseReader = $this->page->getSession()->sendMessage(new Message('Page.printToPDF', $this->options));

        parent::__construct($responseReader);

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setOptions(array $options): self
    {
        \array_map([$this, 'validateOption'], \array_keys($options), $options);

        $this->options = $options;

        return $this;
    }

    /**
     * @param string                $name
     * @param string|int|float|bool $value
     *
     * @throws \InvalidArgumentException
     */
    private function validateOption(string $name, $value): bool
    {
        if (false === \in_array($name, \array_keys(self::OPTIONS))) {
            throw new \InvalidArgumentException("Unknown option '{$name}' for print to pdf.");
        }
        switch (self::OPTIONS[$name]) {
            case self::TYPE_NUMERIC:
                \is_numeric($value) || $this->invalidArgument("Invalid option '{$name}' for print to pdf. Must be numeric.");
                break;
            case self::TYPE_STRING:
                \is_string($value) || $this->invalidArgument("Invalid option '{$name}' for print to pdf. Must be string.");
                break;
            case self::TYPE_BOOLEAN:
                \is_bool($value) || $this->invalidArgument("Invalid option '{$name}' for print to pdf. Must be boolean.");
                break;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    protected function getException(string $message): \Exception
    {
        return new PdfFailed(
            \sprintf('Cannot make a PDF. Reason : %s', $message)
        );
    }

    /**
     * Wrapper to throw exception in expression when running in php 7.
     *
     * @throws \InvalidArgumentException
     */
    private function invalidArgument(string $message): void
    {
        throw new \InvalidArgumentException($message);
    }
}
