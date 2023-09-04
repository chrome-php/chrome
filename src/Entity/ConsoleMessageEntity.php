<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information = '';
    public const TYPE_please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Entity;

/**
 * Console message data.
 *
 * @see https://chromedevtools.github.io/devtools-protocol/tot/Runtime/#event-consoleAPICalled
 */
class ConsoleMessageEntity
{
    public const TYPE_log = 'log';
    public const TYPE_debug = 'debug';
    public const TYPE_info = 'info';
    public const TYPE_error = 'error';
    public const TYPE_warning = 'warning';
    public const TYPE_dir = 'dir';
    public const TYPE_dirxml = 'dirxml';
    public const TYPE_table = 'table';
    public const TYPE_trace = 'trace';
    public const TYPE_clear = 'clear';
    public const TYPE_startGroup = 'startGroup';
    public const TYPE_startGroupCollapsed = 'startGroupCollapsed';
    public const TYPE_endGroup = 'endGroup';
    public const TYPE_assert = 'assert';
    public const TYPE_profile = 'profile';
    public const TYPE_profileEnd = 'profileEnd';
    public const TYPE_count = 'count';
    public const TYPE_timeEnd = 'timeEnd';

    /**
     * Type of the call.
     *
     * @var string
     */
    public $type;

    /**
     * Call arguments, each containing a RemoteObject.
     *
     * `console.log('test')` will result in:
     * ```
     * [
     *   0 => [
     *     'type' => 'string',
     *     'value => 'test',
     *   ],
     * ]
     * ```
     *
     * @see https://chromedevtools.github.io/devtools-protocol/tot/Runtime/#type-RemoteObject
     *
     * @var array
     */
    public $args;

    /**
     * Call timestamp.
     *
     * @var int
     */
    public $timestamp;

    /**
     * @param ConsoleMessageEntity::TYPE_* $type
     */
    public function __construct(
        string $type,
        array $args,
        int $timestamp
    ) {
        $this->type = $type;
        $this->args = $args;
        $this->timestamp = $timestamp;
    }

    /**
     * Readonly trick for php < 8.1.
     *
     * @throws \InvalidArgumentException
     */
    public function __set($name, $value): void
    {
        throw new \InvalidArgumentException('Entity properties are readonly.');
    }
}
