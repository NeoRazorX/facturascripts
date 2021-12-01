<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Contract\MiniLogStorageInterface;

/**
 * Manage all log message information types.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class MiniLog
{

    const DEFAULT_CHANNEL = 'master';
    const LIMIT = 5000;

    /**
     * Current channel.
     *
     * @var string
     */
    private $channel;

    /**
     * @var array
     */
    private static $context = [];

    /**
     * Contains the log data.
     *
     * @var array
     */
    private static $data = [];

    /**
     * @var MiniLogStorageInterface
     */
    private static $storage;

    /**
     * @var Translator|null
     */
    private $translator;

    public function __construct(string $channel = '', $translator = null)
    {
        $this->channel = empty($channel) ? self::DEFAULT_CHANNEL : $channel;
        $this->translator = $translator;
    }

    /**
     * Clears all data for one or all channels.
     *
     * @param string $channel
     */
    public static function clear(string $channel = '')
    {
        if (empty($channel)) {
            self::$data = [];
            return;
        }

        foreach (self::$data as $key => $item) {
            if ($item['channel'] === $channel) {
                unset(self::$data[$key]);
            }
        }
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = [])
    {
        if (FS_DEBUG) {
            $this->log('debug', $message, $context);
        }
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * Gets the stored context value for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    public static function getContext(string $key): string
    {
        return self::$context[$key] ?? '';
    }

    /**
     * Interesting information, advices.
     *
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     */
    public function notice(string $message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Returns all messages for a given channel (or all channels) and some levels.
     *
     * @param string $channel
     * @param array $levels
     *
     * @return array
     */
    public static function read($channel = '', array $levels = []): array
    {
        // TODO: eliminar antes del lanzamiento de la 2021.6
        if (false === is_string($channel)) {
            return [];
        }

        $messages = [];
        foreach (self::$data as $data) {
            if ($channel && $data['channel'] != $channel) {
                continue;
            }

            if ($levels && false === in_array($data['level'], $levels)) {
                continue;
            }

            $messages[] = $data;
        }

        return $messages;
    }

    /**
     * Stores all messages on the default storage.
     *
     * @param string $channel
     *
     * @return bool
     */
    public static function save(string $channel = ''): bool
    {
        if (!isset(self::$storage)) {
            self::$storage = new MiniLogStorage();
        }

        $data = empty($channel) ? self::$data : self::read($channel);
        if (self::$storage->save($data)) {
            self::clear($channel);
            return true;
        }

        return false;
    }

    /**
     * Sets the context value for a given key.
     *
     * @param string $key
     * @param string $value
     */
    public static function setContext(string $key, string $value)
    {
        self::$context[$key] = $value;
    }

    /**
     * Sets a new storage.
     *
     * @param MiniLogStorageInterface $storage
     */
    public static function setStorage(MiniLogStorageInterface $storage)
    {
        self::$storage = $storage;
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
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
     * @param array $context
     */
    private function log(string $level, string $message, array $context = [])
    {
        if (empty($message)) {
            return;
        }

        // if we find this message in the log, we increase the counter
        $finalContext = array_merge($context, self::$context);
        $transMessage = is_null($this->translator) ? $message : $this->translator->trans($message, $context);
        foreach (self::$data as $key => $value) {
            if ($value['channel'] === $this->channel && $value['level'] === $level &&
                $value['message'] === $transMessage && $value['context'] === $finalContext) {
                self::$data[$key]['count']++;
                return;
            }
        }

        // add message
        self::$data[] = [
            'channel' => $this->channel,
            'context' => $finalContext,
            'count' => 1,
            'level' => $level,
            'message' => $transMessage,
            'original' => $message,
            'time' => microtime(true)
        ];
        $this->reduce();
    }

    /**
     * Saves on the default storage and clear all data.
     */
    protected function reduce()
    {
        if (count(self::$data) > self::LIMIT) {
            self::save($this->channel);
        }
    }
}
