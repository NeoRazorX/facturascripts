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

    /**
     * Contains the log data.
     *
     * @var array
     */
    private static $dataLog = [];

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
    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     */
    public function debug($message, array $context = [])
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
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Returns specified level messages or all.
     *
     * @param array $levels
     *
     * @return array
     */
    public function read(array $levels = ['notice', 'warning', 'error', 'critical'])
    {
        $messages = [];
        foreach (self::$dataLog as $data) {
            if (in_array($data['level'], $levels, false) && $data['message'] !== '') {
                $messages[] = $data;
            }
        }

        return $messages;
    }

    /**
     * SQL history.
     *
     * @param string $message
     * @param array  $context
     */
    public function sql($message, array $context = [])
    {
        $this->log('sql', $message, $context);
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
    public function warning($message, array $context = [])
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
    private function log($level, $message, array $context = [])
    {
        self::$dataLog[] = [
            'context' => $context,
            'level' => $level,
            'message' => $message,
            'time' => time(),
        ];
    }
}
