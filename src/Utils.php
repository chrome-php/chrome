<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\OperationTimedOut;

class Utils
{

    /**
     * Iterates on the given generator until the generator returns a value or the given timeout is reached.
     *
     * When the generator yields a value, this value is the time in microseconds to wait before trying again.
     *
     * Example waiting for a process to complete:
     *
     * ```php
     *  // wait for process to close
     *  $generator = function (Process $process) {
     *      while ($process->isRunning()) {
     *          yield 2 * 1000; // wait for 2ms
     *      }
     *  };
     *
     *  $timeout = 8 * 1000 * 1000; // 8 seconds
     *
     *  try {
     *      Utils::tryWithTimeout($timeout, $generator($this->process));
     *  } catch (OperationTimedOut $e) {
     *      // log
     *      $this->logger->debug('process: process didn\'t close by itself');
     *  }
     * ```
     *
     * @param $timeoutMicroSec
     * @param $generator
     * @param callable|null $onTimeout
     * @return mixed|null
     * @throws OperationTimedOut
     */
    public static function tryWithTimeout($timeoutMicroSec, $generator, callable $onTimeout = null)
    {
        $waitUntilMicroSec = microtime(true) * 1000 * 1000 + $timeoutMicroSec;
        foreach ($generator as $k => $v) {
            if($k === 0) {
                if (microtime(true) * 1000 * 1000 + (is_int($generator) ? $generator : 0) >= $waitUntilMicroSec) {
                    if ($onTimeout) {
                        // if callback was set execute it
                        return $onTimeout();
                    } else {
                        if ($timeoutMicroSec > 1000 * 1000) {
                            $timeoutPhrase = (int)($timeoutMicroSec / (1000 * 1000)) . 'sec';
                        } elseif ($timeoutMicroSec > 1000) {
                            $timeoutPhrase = (int)($timeoutMicroSec / 1000) . 'ms';
                        } else {
                            $timeoutPhrase = (int)($timeoutMicroSec) . 'μs';
                        }
                        throw new OperationTimedOut('Operation timed out (' . $timeoutPhrase . ')');
                    }
                }
                usleep($v);
            } else {
                if ($generator instanceof \Generator) {
                    return $generator->current();
                }
            }
        }
    }

    /**
     * Closes all pages for the given connection
     * @param Connection $connection
     * @throws OperationTimedOut
     */
    public static function closeAllPage(Connection $connection)
    {
        // get targets
        $targetsResponse = $connection->sendMessageSync(new Message('Target.getTargets'), 2000);

        if ($targetsResponse->isSuccessful()) {
            foreach ($targetsResponse['result']['targetInfos'] as $target) {
                if ($target['type'] === 'page') {
                    $connection->sendMessageSync(
                        new Message('Target.closeTarget', ['targetId' => $target['targetId']])
                    , 500);
                }
            }
        }
    }
}
