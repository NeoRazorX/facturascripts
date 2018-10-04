<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

namespace FacturaScripts\Core\Base\DebugBar;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * This class collects all PHP errors, notice, advices, trigger_error, ...
 * Supports 15 different types included.
 *
 * Based on MessagesCollector style because includes a search bar and a filter on bottom.
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class PHPCollector extends DataCollector implements Renderable
{
    /**
     * Collector name.
     *
     * @var string
     */
    protected $name;

    /**
     * List of messages. Each item includes:
     *  'message', 'message_html', 'is_string', 'label', 'time'.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * PHPCollector constructor.
     *
     * @param string $name The name used by this collector widget.
     */
    public function __construct($name = 'Error handler')
    {
        $this->name = $name;
        set_error_handler([$this, 'errorHandler'], E_ALL);
    }

    /**
     * Called by the DebugBar when data needs to be collected.
     *
     * @return array Collected data.
     */
    public function collect()
    {
        $messages = $this->getMessages();
        return [
            'count' => count($messages),
            'messages' => $messages,
        ];
    }

    /**
     * Returns the unique name of the collector.
     *
     * @return string The widget name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns a hash where keys are control names and their values an array of options as defined in
     * {@see DebugBar\JavascriptRenderer::addControl()}
     *
     * @return array Needed details to render the widget.
     */
    public function getWidgets()
    {
        $name = $this->getName();
        return [
            "$name" => [
                'icon' => 'list',
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                'map' => "$name.messages",
                'default' => '[]',
            ],
            "$name:badge" => [
                'map' => "$name.count",
                'default' => "null",
            ],
        ];
    }

    /**
     * Exception error handler. Called from constructor with set_error_handler to add all details.
     *
     * @param int    $severity Error type.
     * @param string $message Message of error.
     * @param string $fileName File where error is generated.
     * @param int    $line Line number where error is generated.
     */
    public function errorHandler($severity, $message, $fileName, $line)
    {
        for ($i = 0; $i < 15; $i++) {
            if ($type = $severity & pow(2, $i)) {
                $label = $this->friendlyErrorType($type);
                $this->messages[] = [
                    'message' => $message . ' (' . $fileName . ':' . $line . ')',
                    'message_html' => null,
                    'is_string' => true,
                    'label' => $label,
                    'time' => microtime(true),
                ];
            }
        }
    }

    /**
     * Returns a list of messages ordered by their timestamp.
     *
     * @return array A list of messages ordered by time.
     */
    public function getMessages()
    {
        $messages = $this->messages;

        usort($messages, function ($itemA, $itemB) {
            if ($itemA['time'] === $itemB['time']) {
                return 0;
            }
            return $itemA['time'] < $itemB['time'] ? -1 : 1;
        });

        return $messages;
    }

    /**
     * Return error name from error code.
     *
     * @info http://php.net/manual/es/errorfunc.constants.php
     *
     * @param int $type Error code.
     *
     * @return string Error name.
     */
    private function friendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR:
                return 'ERROR';
            case E_WARNING:
                return 'WARNING';
            case E_PARSE:
                return 'PARSE';
            case E_NOTICE:
                return 'NOTICE';
            case E_CORE_ERROR:
                return 'CORE_ERROR';
            case E_CORE_WARNING:
                return 'CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'COMPILE_WARNING';
            case E_USER_ERROR:
                return 'USER_ERROR';
            case E_USER_WARNING:
                return 'USER_WARNING';
            case E_USER_NOTICE:
                return 'USER_NOTICE';
            case E_STRICT:
                return 'STRICT';
            case E_RECOVERABLE_ERROR:
                return 'RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'DEPRECATED';
            case E_USER_DEPRECATED:
                return 'USER_DEPRECATED';
            default:
                return '';
        }
    }
}
