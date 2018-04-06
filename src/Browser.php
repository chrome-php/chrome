<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;

class Browser
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Target[]
     */
    protected $targets = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->connection->on(Connection::EVENT_TARGET_CREATED, function (array $params) {

            // create a session for the target
            $session = $this->connection->createSession($params['targetInfo']['targetId']);

            // create and store the target
            $this->targets[$params['targetInfo']['targetId']] = new Target(
                $params['targetInfo'],
                $session
            );
        });
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Closes the browser
     */
    public function close()
    {
        // TODO
        throw new \Exception('Not implemented yet, see ProcessAwareBrowser instead');
    }

    /**
     * Creates a new page
     * @throws NoResponseAvailable
     * @throws CommunicationException
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

        // make sure target was created (via Target.targetCreated event)
        if (!array_key_exists($targetId, $this->targets)) {
            throw new \RuntimeException('Target could not be created for page');
        }
        $target = $this->targets[$targetId];

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

        // Page.setLifecycleEventsEnabled
        $page->getSession()->sendMessageSync(new Message('Page.setLifecycleEventsEnabled', ['enabled' => true]));

        return $page;
    }
}
