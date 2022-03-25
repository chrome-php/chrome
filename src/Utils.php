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
use HeadlessChromium\Dom\Selector\Selector;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\JavascriptException;
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
     * @param int           $timeoutMicroSec
     * @param \Generator    $generator
     * @param callable|null $onTimeout
     *
     * @throws OperationTimedOut
     *
     * @return mixed
     */
    public static function tryWithTimeout(int $timeoutMicroSec, \Generator $generator, callable $onTimeout = null)
    {
        $waitUntilMicroSec = \hrtime(true) / 1000 + $timeoutMicroSec;

        foreach ($generator as $v) {
            // if timeout reached or if time+delay exceed timeout stop the execution
            if (\hrtime(true) / 1000 + (int) $v >= $waitUntilMicroSec) {
                if (null !== $onTimeout) {
                    // if callback was set execute it
                    return $onTimeout();
                }
                throw OperationTimedOut::createFromTimeout($timeoutMicroSec);
            }

            \usleep((int) $v);
        }

        return $generator->getReturn();
    }

    /**
     * Closes all pages for the given connection.
     *
     * @param Connection $connection
     */
    public static function closeAllPage(Connection $connection): void
    {
        // get targets
        $targetsResponse = $connection->sendMessageSync(new Message('Target.getTargets'));

        if ($targetsResponse->isSuccessful()) {
            foreach ($targetsResponse['result']['targetInfos'] as $target) {
                if ('page' === $target['type']) {
                    $connection->sendMessageSync(
                        new Message('Target.closeTarget', ['targetId' => $target['targetId']])
                    );
                }
            }
        }
    }

    /**
     * @throws CommunicationException
     * @throws Exception\EvaluationFailed
     * @throws JavascriptException
     *
     * @return mixed
     */
    public static function getElementPositionFromPage(Page $page, Selector $selector, int $position = 1)
    {
        $elementCount = $page
            ->evaluate(\sprintf('JSON.parse(JSON.stringify(%s));', $selector->expressionCount()))
            ->getReturnValue();

        $position = \max(1, $position);
        $position = \min($position, $elementCount);

        return $page
            ->evaluate(\sprintf('JSON.parse(JSON.stringify(%s.getBoundingClientRect()));', $selector->expressionFindOne($position)))
            ->getReturnValue();
    }
}
