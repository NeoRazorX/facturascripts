<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

/**
 * Manage all log message information types.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MiniLog
{

    const ALL_LEVELS = ['critical', 'debug', 'error', 'info', 'notice', 'warning'];
    const DEFAULT_CHANNEL = 'master';
    const DEFAULT_LEVELS = ['critical', 'error', 'info', 'notice', 'warning'];

    /**
     * Current channel.
     *
     * @var string
     */
    private $channel;

    /**
     * Contains the log data.
     *
     * @var array
     */
    private static $dataLog = [];

    /**
     * 
     * @param string $channel
     */
    public function __construct(string $channel = '')
    {
        $this->channel = empty($channel) ? self::DEFAULT_CHANNEL : $channel;
    }

    /**
     * Clean the log.
     */
    public function clear()
    {
        self::$dataLog = [];
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    public function critical(string $message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     */
    public function debug(string $message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public function error(string $message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * Interesting information, advices.
     *
     * @param string $message
     * @param array  $context
     */
    public function info(string $message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public function notice(string $message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Returns specified level messages of this channel.
     * 
     * @param array $levels
     *
     * @return array
     */
    public function read(array $levels = self::DEFAULT_LEVELS): array
    {
        $messages = [];
        foreach (self::$dataLog as $data) {
            if ($data['channel'] === $this->channel && in_array($data['level'], $levels, false)) {
                $messages[] = $data;
            }
        }

        return $messages;
    }

    /**
     * Returns specified level messages of all channels.
     * 
     * @param array $levels
     *
     * @return array
     */
    public function readAll(array $levels = self::DEFAULT_LEVELS): array
    {
        $messages = [];
        foreach (self::$dataLog as $data) {
            if (in_array($data['level'], $levels, false)) {
                $messages[] = $data;
            }
        }

        return $messages;
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning(string $message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    protected function log(string $level, string $message, array $context = [])
    {
        if (!empty($message)) {
            self::$dataLog[] = [
                'channel' => $this->channel,
                'context' => $context,
                'level' => $level,
                'message' => $message,
                'microtime' => microtime(true),
                'time' => time(),
            ];
        }
    }
}
