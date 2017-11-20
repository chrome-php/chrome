<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;

class Browser
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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

        // todo handle error

        // create target session
        $session = $this->connection->createSession($response['result']['targetId']);

        // create page
        return new Page($session);
    }
}
