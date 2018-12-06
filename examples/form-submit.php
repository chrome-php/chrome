<?php

require __DIR__ . '/../vendor/autoload.php';

use HeadlessChromium\BrowserFactory;

// open browser
$factory = new BrowserFactory();
$browser = $factory->createBrowser();

// navigate to a page with a form
$page = $browser->createPage();
$page->navigate('file://' . __DIR__ . '/html/form.html')->waitForNavigation();

// put 'hello' in the input and submit the form
$evaluation = $page->evaluate(
'(() => {
        document.querySelector("#myinput").value = "hello";
        document.querySelector("#myform").submit();
    })()'
);

// wait for the page to be reloaded
$evaluation->waitForPageReload();

// get value in the new page
$value = $page->evaluate('document.querySelector("#value").innerHTML')->getReturnValue();

var_dump($value);
