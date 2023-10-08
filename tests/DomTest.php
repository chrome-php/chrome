<?php

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Dom\Dom
 */
class DomTest extends BaseTestCase
{
    public static Browser\ProcessAwareBrowser $browser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate(self::sitePath($file))->waitForNavigation();

        return $page;
    }

    public function testSearchByCssSelector(): void
    {
        $page = $this->openSitePage('domForm.html');
        $element = $page->dom()->querySelector('button');
        $notFoundElement = $page->dom()->querySelector('img');

        self::assertNotNull($element);
        self::assertNull($notFoundElement);
    }

    public function testSearchByCssSelectorAll(): void
    {
        $page = $this->openSitePage('domForm.html');

        $elements = $page->dom()->querySelectorAll('div');

        self::assertCount(2, $elements);

        $notFoundElements = $page->dom()->querySelectorAll('img');
        self::assertCount(0, $notFoundElements);
    }

    public function testSearchByXpath(): void
    {
        $page = $this->openSitePage('domForm.html');

        $elements = $page->dom()->search('//*/div');

        self::assertCount(2, $elements);
    }

    public function testClick(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#myinput');

        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        self::assertFalse($value);

        // press the Tab key
        $element->click();

        // test the the focus switched to #myinput
        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        self::assertTrue($value);
    }

    public function testType(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#myinput');

        $element->click();
        $element->sendKeys('bar');

        $value = $page
            ->evaluate('document.querySelector("#myinput").value;')
            ->getReturnValue();

        // checks if the input contains the typed text
        self::assertSame('bar', $value);
    }

    public function testGetText(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        $value = $element->getText();

        self::assertSame('bar', $value);
    }

    public function testGetAttribute(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        $value = $element->getAttribute('type');

        self::assertSame('foo', $value);
    }

    public function testSetAttribute(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        $element->setAttributeValue('type', 'hello');

        $value = $element->getAttribute('type');

        self::assertSame('hello', $value);
    }

    public function testUploadFile(): void
    {
        $page = $this->openSitePage('domForm.html');
        $file = self::sitePath('domForm.html');

        $element = $page->dom()->querySelector('#myfile');
        $element->sendFile($file);

        $value = $page
            ->evaluate('document.querySelector("#myfile").value;')
            ->getReturnValue();

        // check if the file was selected
        self::assertStringEndsWith(\basename($file), $value);
    }

    public function testUploadFiles(): void
    {
        $page = $this->openSitePage('domForm.html');
        $files = [
            self::sitePath('domForm.html'),
            self::sitePath('form.html'),
        ];

        $element = $page->dom()->querySelector('#myfiles');
        $element->sendFiles($files);

        $value1 = $page->evaluate('document.querySelector("#myfiles").files[0].name;')->getReturnValue();
        $value2 = $page->evaluate('document.querySelector("#myfiles").files[1].name;')->getReturnValue();

        // check if the files were selected
        self::assertStringEndsWith(\basename($files[0]), $value1);
        self::assertStringEndsWith(\basename($files[1]), $value2);
    }

    public function testSetHTML(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');
        $element->setHTML('<span id="span">hello</span>');

        $value = $page->dom()->querySelector('#span')->getHTML();

        self::assertCount(0, $page->dom()->querySelectorAll('#div1'));

        self::assertEquals('<span id="span">hello</span>', $value);
    }
}
