<?php

declare(strict_types=1);

namespace HeadlessChromium\Dom;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Page;

class Dom extends Node
{
    public function __construct(Page $page)
    {
        $message = new Message('DOM.getDocument');
        $stream = $page->getSession()->sendMessage($message);
        $response = $stream->waitForResponse(1000);

        $rootNodeId = $response->getResultData('root')['nodeId'];

        parent::__construct($page, $rootNodeId);
    }

    public function search(string $selector): array
    {
        $message = new Message('DOM.performSearch', [
            'query' => $selector,
        ]);
        $response = $this->page->getSession()->sendMessageSync($message);

        $this->assertNotError($response);

        $searchId = $response->getResultData('searchId');
        $count = $response->getResultData('resultCount');

        if (0 === $count) {
            return [];
        }
        $message = new Message('DOM.getSearchResults', [
            'searchId' => $searchId,
            'fromIndex' => 0,
            'toIndex' => $count,
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
}
