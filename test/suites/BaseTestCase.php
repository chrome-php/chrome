<?php
/**
 * @license see LICENSE
 */


namespace HeadlessChromium\Test;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{

    public function sendMessageToTargetArray($sessionId, $messageArray)
    {
        return [
            'id' => '%id',
            'method' => 'Target.sendMessageToTarget',
            'params' => [
                'message' => json_encode($messageArray),
                'sessionId' => $sessionId
            ]
        ];
    }

    public function assertDataSentEquals(array $assert, array $sent)
    {
        // count must match
        $this->assertEquals(
            count($assert),
            count($sent),
            sprintf(
                'Failed asserting that %s messages sent matches expected %s messages sent',
                count($sent),
                count($assert)
            )
        );

        // decode sent data
        foreach ($sent as $k => $datum) {
            $sent[$k] = json_decode($datum, true);
            $this->assertInternalType('array', $sent[$k]);
        }

        // test sent data is equal to
        foreach ($assert as $k => $datum) {
            // replace id with message id
            if (isset($datum['id']) && $datum['id'] == '%id') {
                $this->assertArrayHasKey('id', $sent[$k]);
                $datum['id'] = $sent[$k]['id'];
            }

            if ($datum['method'] == 'Target.sendMessageToTarget' && isset($datum['params']['message'])) {
                $subMessage = json_decode($datum['params']['message'], true);
                if (isset($subMessage['id']) && $subMessage['id'] == '%id') {
                    $subMessageSent = json_decode($sent[$k]['params']['message'], true);
                    if ($subMessageSent && $subMessageSent['id']) {
                        $subMessage['id'] = $subMessageSent['id'];
                        $datum['params']['message'] = json_encode($subMessage);
                    }
                }
            }

            $this->assertEquals($datum, $sent[$k]);
        }
    }
}
