<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Response;
use HeadlessChromium\Exception\DomException;
use HeadlessChromium\Page;

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
     * Node constructor.
     */
    public function __construct(Page $page, int $nodeId)
    {
        $this->page = $page;
        $this->nodeId = $nodeId;
    }

    /**
     * @return NodeAttributes
     */
    public function getAttributes(): NodeAttributes
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
     *
     * @return Node|null
     */
    public function querySelector($selector): ?self
    {
        $message = new Message('DOM.querySelector', [
            'nodeId' => $this->nodeId,
            'selector' => $selector,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);
        $this->assertNotError($response);

        $nodeId = $response->getResultData('nodeId');

        if (null !== $nodeId) {
            return new self($this->page, $nodeId);
        }

        return null;
    }

    /**
     * @param string $selector
     *
     * @return array
     */
    public function querySelectorAll($selector): array
    {
        $message = new Message('DOM.querySelectorAll', [
            'nodeId' => $this->nodeId,
            'selector' => $selector,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $nodes = [];
        $nodeIds = $response->getResultData('nodeIds');
        foreach ($nodeIds as $nodeId) {
            $nodes[] = new self($this->page, $nodeId);
        }

        return $nodes;
    }

    /**
     * @return void
     */
    public function focus(): void
    {
        $message = new Message('DOM.focus', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getAttribute($name): ?string
    {
        return $this->getAttributes()->get($name);
    }

    /**
     * @return NodePosition|null
     */
    public function getPosition(): ?NodePosition
    {
        $message = new Message('DOM.getBoxModel', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $points = $response->getResultData('model')['content'];

        if (null !== $points) {
            return new NodePosition($points);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function hasPosition(): bool
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
        return \strip_tags($this->getHTML());
    }

    /**
     * @return void
     */
    public function scrollIntoView(): void
    {
        $message = new Message('DOM.scrollIntoViewIfNeeded', [
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    /**
     * @throws DomException
     *
     * @return void
     */
    public function click(): void
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
     *
     * @return void
     */
    public function sendKeys($text): void
    {
        $this->scrollIntoView();
        $this->focus();
        $this->page->keyboard()
            ->typeText($text);
    }

    /**
     * @param string $filePath
     *
     * @return void
     */
    public function sendFile($filePath): void
    {
        $message = new Message('DOM.setFileInputFiles', [
            'files' => [$filePath],
            'nodeId' => $this->nodeId,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);
    }

    /**
     * @param Response $response
     *
     * @throws DomException
     *
     * @return void
     */
    public function assertNotError($response): void
    {
        if (!$response->isSuccessful()) {
            throw new DOMException($response->getErrorMessage());
        }
    }
}
