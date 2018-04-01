<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Target;

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
     * @param string|null $uri url to open the page on
     * @param array|null $dimensions dimensions of the window: [width, height]
     * @throws Exception\NoResponseAvailable
     * @return Page
     */
    public function createPage(
        string $uri = null,
        array $dimensions = null
    ): Page {

        // page url
        $params = ['url' => $uri ?? 'about:blank'];

        // page dimensions
        if ($dimensions) {
            $params['width']  = $dimensions[0];
            $params['height'] = $dimensions[1];
        }

        // create page
        $response = $this->connection->sendMessageSync(new Message('Target.createTarget', $params));

        // get created target id
        $targetId = $response['result']['targetId'];

        // todo handle error

        if (!array_key_exists($targetId, $this->targets)) {
            throw new \RuntimeException('Target could not be created for page');
        }

        // create target session

        // create page
        return new Page($this->targets[$targetId]);
    }
}
