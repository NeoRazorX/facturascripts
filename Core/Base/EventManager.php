<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of EventManager
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EventManager
{

    /**
     * @var array
     */
    private static $listeners = [];

    /**
     * 
     * @param string   $eventName
     * @param callable $callable
     * @param int      $priority
     */
    public static function attach(string $eventName, $callable, $priority = 0)
    {
        if (!isset(self::$listeners[$eventName])) {
            self::$listeners[$eventName] = [];
        }

        self::$listeners[$eventName][] = ['callable' => $callable, 'priority' => $priority];
    }

    /**
     * 
     * @param string $eventName
     * @param object $object
     */
    public static function trigger(string $eventName, &$object)
    {
        if (!isset(self::$listeners[$eventName])) {
            return;
        }

        foreach (static::get($eventName) as $listener) {
            /// event ends if false is returned
            if (false === call_user_func($listener, $object)) {
                break;
            }
        }
    }

    /**
     * 
     * @param string $eventName
     *
     * @return array
     */
    private static function get(string $eventName)
    {
        /// sort by priority
        uasort(self::$listeners[$eventName], function ($item1, $item2) {
            if ($item1['priority'] > $item2['priority']) {
                return -1;
            } elseif ($item1['priority'] < $item2['priority']) {
                return 1;
            }

            return 0;
        });

        $listeners = [];
        foreach (self::$listeners[$eventName] as $item) {
            $listeners[] = $item['callable'];
        }
        return $listeners;
    }
}
