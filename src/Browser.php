<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;

class Browser
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array<string,Target>
     */
    protected $targets = [];

    /**
     * @var array<string,Page>
     */
    protected $pages = [];

    /**
     * A preScript to be automatically added on every new pages.
     *
     * @var string|null
     */
    protected $pagePreScript;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        // listen for target created
        $this->connection->on(Connection::EVENT_TARGET_CREATED, function (array $params): void {
            // create and store the target
            $this->targets[$params['targetInfo']['targetId']] = new Target($params['targetInfo'], $this->connection);
        });

        // listen for target info changed
        $this->connection->on(Connection::EVENT_TARGET_INFO_CHANGED, function (array $params): void {
            // get target by id
            $target = $this->getTarget($params['targetInfo']['targetId']);

            if ($target) {
                $target->targetInfoChanged($params['targetInfo']);
            }
        });

        // listen for target destroyed
        $this->connection->on(Connection::EVENT_TARGET_DESTROYED, function (array $params): void {
            // get target by id
            $target = $this->getTarget($params['targetId']);

            if ($target) {
                // remove the page
                unset($this->pages[$params['targetId']]);
                // remove the target
                unset($this->targets[$params['targetId']]);
                $target->destroy();
                $this->connection
                    ->getLogger()
                    ->debug('✘ target('.$params['targetId'].') was destroyed and unreferenced.');
            }
        });

        // enable target discovery
        $connection->sendMessageSync(new Message('Target.setDiscoverTargets', ['discover' => true]));

        // set up http headers
        $headers = $connection->getConnectionHttpHeaders();
        $connection->sendMessageSync(new Message('Network.setExtraHTTPHeaders', $headers));
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set a preScript to be added on every new pages.
     * Use null to disable it.
     *
     * @param string|null $script
     */
    public function setPagePreScript(string $script = null): void
    {
        $this->pagePreScript = $script;
    }

    /**
     * Closes the browser.
     *
     * @throws \Exception
     */
    public function close(): void
    {
        $this->sendCloseMessage();
    }

    /**
     * Send close message to the browser.
     *
     * @throws OperationTimedOut
     */
    final public function sendCloseMessage(): void
    {
        $r = $this->connection->sendMessageSync(new Message('Browser.close'));
        if (!$r->isSuccessful()) {
            // log
            $this->connection->getLogger()->debug('process: ✗ could not close gracefully');
            throw new \Exception('cannot close, Browser.close not supported');
        }
    }

    /**
     * Creates a new page.
     *
     * @throws NoResponseAvailable
     * @throws CommunicationException
     * @throws OperationTimedOut
     *
     * @return Page
     */
    public function createPage(): Page
    {
        // page url
        $params = ['url' => 'about:blank'];

        // create page and get target id
        $response = $this->connection->sendMessageSync(new Message('Target.createTarget', $params));
        $targetId = $response['result']['targetId'];

        // todo handle error

        $target = $this->getTarget($targetId);
        if (!$target) {
            throw new \RuntimeException('Target could not be created for page.');
        }

        $page = $this->getPage($targetId);

        return $page;
    }

    /**
     * @param string $targetId
     *
     * @return Target|null
     */
    public function getTarget($targetId)
    {
        // make sure target was created (via Target.targetCreated event)
        if (!\array_key_exists($targetId, $this->targets)) {
            return null;
        }

        return $this->targets[$targetId];
    }

    /**
     * @return Target[]
     */
    public function getTargets()
    {
        return \array_values($this->targets);
    }

    /**
     * @param string $targetId
     *
     * @return Page|null
     */
    public function getPage($targetId)
    {
        if (\array_key_exists($targetId, $this->pages)) {
            return $this->pages[$targetId];
        }

        $target = $this->getTarget($targetId);

        if ('page' !== $target->getTargetInfo('type')) {
            return null;
        }

        // get initial frame tree
        $frameTreeResponse = $target->getSession()->sendMessageSync(new Message('Page.getFrameTree'));

        // make sure frame tree was found
        if (!$frameTreeResponse->isSuccessful()) {
            throw new ResponseHasError('Cannot read frame tree. Please, consider upgrading chrome version.');
        }

        // create page
        $page = new Page($target, $frameTreeResponse['result']['frameTree']);

        // Page.enable
        $page->getSession()->sendMessageSync(new Message('Page.enable'));

        // Network.enable
        $page->getSession()->sendMessageSync(new Message('Network.enable'));

        // Runtime.enable
        $page->getSession()->sendMessageSync(new Message('Runtime.enable'));

        // Page.setLifecycleEventsEnabled
        $page->getSession()->sendMessageSync(new Message('Page.setLifecycleEventsEnabled', ['enabled' => true]));

        // add prescript
        if ($this->pagePreScript) {
            $page->addPreScript($this->pagePreScript);
        }

        $this->pages[$targetId] = $page;

        return $page;
    }

    /**
     * @return Page[]
     */
    public function getPages()
    {
        $ids = \array_keys($this->targets);

        $pages = \array_filter(\array_map([$this, 'getPage'], $ids));

        return \array_values($pages);
    }
}
