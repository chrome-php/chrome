<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\DomException;
use HeadlessChromium\Page;

/**
 * Class Node
 */
class Node
{
    /**
     * @var Page
     */
    protected $page;

    /**
     * @var int
     */
    protected $nodeId;

    /**
     * @param Page $page
     * @param int $nodeId
     */
    public function __construct($page, $nodeId)
    {
        $this->page = $page;
        $this->nodeId = $nodeId;
    }

    /**
     * @return NodeAttributes
     */
    public function getAttributes()
    {
        $message = new Message('DOM.getAttributes', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $attributes = $response->getResultData('attributes');

        return new NodeAttributes($attributes);
    }

    /**
     * @param string $selector
     * @return Node|void
     */
    public function querySelector($selector)
    {
        $message = new Message('DOM.querySelector', [
            'nodeId' => $this->nodeId,
            'selector' => $selector
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);
        $this->assertNotError($response);

        $nodeId = $response->getResultData('nodeId');

        if (null !== $nodeId) {
            return new Node($this->page, $nodeId);
        }
    }


    /**
     * @param string $selector
     * @return array
     */
    public function querySelectorAll($selector)
    {
        $message = new Message('DOM.querySelectorAll', [
            'nodeId' => $this->nodeId,
            'selector' => $selector
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $nodes = [];
        $nodeIds = $response->getResultData('nodeIds');
        foreach ($nodeIds as $nodeId) {
            $nodes[] = new Node($this->page, $nodeId);
        }

        return $nodes;
    }

    /**
     * @return void
     */
    public function focus()
    {
        $message = new Message('DOM.focus', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    /**
     * @param string $name
     * @return string |null
     */
    public function getAttribute($name)
    {
        return $this->getAttributes()->get($name);
    }

    /**
     * @return NodePosition|null
     */
    public function getPosition()
    {
        $message = new Message('DOM.getBoxModel', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $points = $response->getResultData('model')['content'];

        if ($points !== null) {
            return new NodePosition($points);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function hasPosition()
    {
        return null !== $this->getPosition();
    }

    /**
     * @return mixed
     */
    public function getHTML()
    {
        $message = new Message('DOM.getOuterHTML', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        return $response->getResultData('outerHTML');
    }

    /**
     * @return string
     */
    public function getText()
    {
        return strip_tags($this->getHTML());
    }

    /**
     * @return void
     */
    public function scrollIntoView()
    {
        $message = new Message('DOM.scrollIntoViewIfNeeded', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    /**
     * @return void
     * @throws DomException
     */
    public function click()
    {
        if (false === $this->hasPosition()) {
            throw new DomException('Failed to click element without position');
        }
        $this->scrollIntoView();
        $position = $this->getPosition();
        $this->page->mouse()
            ->move($position->getCenterX(), $position->getCenterY())                             // Moves mouse to position x=10;y=20
            ->click();
    }

    /**
     * @param string $text
     * @return void
     */
    public function sendKeys($text)
    {
        $this->scrollIntoView();
        $this->focus();
        $this->page->keyboard()
            ->typeText($text);
    }

    /**
     * @param $filePath
     * @return void
     */
    public function sendFile($filePath)
    {
        $message = new Message('DOM.setFileInputFiles', [
            'files' => [$filePath],
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    public function assertNotError($response){
        if(!$response->isSuccessful()){
            throw new DOMException($response->getErrorMessage());
        }
    }
}
