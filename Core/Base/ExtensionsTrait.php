<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

trait ExtensionsTrait
{

    /**
     * Stores class extensions.
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * Executes the first matched extension.
     * 
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($name, $arguments = [])
    {
        foreach (static::$extensions as $ext) {
            if ($ext['name'] === $name && $ext['function'] instanceof Closure) {
                return \call_user_func_array($ext['function']->bindTo($this, static::class), $arguments);
            }
        }

        throw new BadMethodCallException('Method ' . $name . ' does not exist on class ' . static::class . '.');
    }

    /**
     * 
     * @param mixed $extension
     */
    public static function addExtension($extension)
    {
        $methods = (new ReflectionClass($extension))->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
            $method->setAccessible(true);
            \array_unshift(self::$extensions, ['name' => $method->name, 'function' => $method->invoke($extension)]);
        }
    }

    /**
     * 
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function pipe($name, ...$arguments)
    {
        $return = null;
        foreach (static::$extensions as $ext) {
            if ($ext['name'] !== $name) {
                continue;
            } elseif ($ext['function'] instanceof Closure) {
                $return = \call_user_func_array($ext['function']->bindTo($this, static::class), $arguments);
            }

            if ($return !== null) {
                break;
            }
        }

        return $return;
    }
}
