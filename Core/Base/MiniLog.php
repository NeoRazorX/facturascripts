<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Log;

/**
 * Manage all log message information types.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MiniLog
{
    /**
     * All available log types
     */
    const ALL_TYPES = ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * Contains the log data.
     *
     * @var array
     */
    private static $dataLog;

    /**
     * Contains allowed types to store.
     *
     * @var array
     */
    private static $allowed;

    /**
     * MiniLog constructor.
     */
    public function __construct()
    {
        if (self::$dataLog === null) {
            self::$dataLog = [];
            self::$allowed = [];

            $this->loadAllowed();
        }
    }

    /**
     * Load allowed types.
     */
    private function loadAllowed()
    {
        $dataBase = new DataBase();
        if ($dataBase->connect()) {
            $settings = new AppSettings();
            $settings->load();
            $dataBase->close();
        }
        foreach (self::ALL_TYPES as $type) {
            if (!\in_array($type, self::$allowed)) {
                if (AppSettings::get('log', $type, false)) {
                    self::$allowed[] = $type;
                }
            }
        }
    }
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, dataBase unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
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
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     */
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
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
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        $data = [
            'time' => time(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        self::$dataLog[] = $data;
        $this->persist($data);
    }

    /**
     * Save data to permanent log.
     *
     * @param array $data
     */
    private function persist(array $data)
    {
        if (\in_array($data['level'], self::$allowed, false)) {
            $log = new Log();
            $log->time = $data['time'];
            $log->level = $data['level'];
            $log->message = $data['message'];
            $log->save();
        }
    }

    /**
     * Returns specified level messages or all.
     *
     * @param array $levels
     *
     * @return array
     */
    public function read(array $levels = self::ALL_TYPES)
    {
        $messages = [];

        foreach (self::$dataLog as $data) {
            if (\in_array($data['level'], $levels, false)) {
                if ($data['message'] !== '') {
                    $messages[] = $data;
                }
            }
        }

        return $messages;
    }

    /**
     * Clean the log.
     */
    public function clear()
    {
        self::$dataLog = [];
    }
}
