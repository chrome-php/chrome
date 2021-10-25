<?php

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Dom\Dom
 */
class DomTest extends BaseTestCase
{
    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

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

        //assert element not null
        $this->assertNotNull($element);
    }

    public function testSearchByCssSelectorAll(): void
    {
        $page = $this->openSitePage('domForm.html');

        $elements = $page->dom()->querySelectorAll('div');

        //assert found two elements
        $this->assertEquals(\count($elements), 2);
    }

    public function testSearchByXpath(): void
    {
        $page = $this->openSitePage('domForm.html');

        $elements = $page->dom()->search('//*/div');

        //assert found two elements
        $this->assertEquals(\count($elements), 2);
    }

    public function testClick(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#myinput');

        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        $this->assertFalse($value);

        // press the Tab key
        $element->click();

        // test the the focus switched to #myinput
        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        $this->assertTrue($value);
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
        $this->assertEquals('bar', $value);
    }

    public function testGetText(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        //Getting element contents
        $value = $element->getText();

        $this->assertEquals('bar', $value);
    }

    public function testGetAttribute(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        //Getting element contents
        $value = $element->getAttribute('type');

        $this->assertEquals('foo', $value);
    }

    public function testSetAttribute(): void
    {
        $page = $this->openSitePage('domForm.html');

        $element = $page->dom()->querySelector('#div1');

        //Getting element contents
        $element->setAttributeValue('type', 'hello');

        $value = $element->getAttribute('type');

        $this->assertEquals('hello', $value);
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

        // check if file was uploaded
        $this->assertNotEmpty($value);
    }
}
