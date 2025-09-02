<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

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
     * Cache for extension lookups by method name.
     *
     * @var array
     */
    protected static $extensionCache = [];

    /**
     * Executes the first matched extension.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($name, $arguments = [])
    {
        // Build cache if needed
        if (!isset(static::$extensionCache[$name])) {
            $this->buildExtensionCache($name);
        }

        // Execute first extension found (respecting priority)
        if (!empty(static::$extensionCache[$name])) {
            return call_user_func_array(static::$extensionCache[$name][0]->bindTo($this, static::class), $arguments);
        }

        throw new BadMethodCallException('Method ' . $name . ' does not exist on class ' . static::class . '.');
    }

    /**
     * @param mixed $extension
     * @param int $priority Priority for all methods in this extension (0-1000, default 100)
     */
    public static function addExtension($extension, int $priority = 100): void
    {
        $methods = (new ReflectionClass($extension))->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE);
        foreach ($methods as $method) {
            // Skip magic methods and constructors
            if (strpos($method->name, '__') === 0) {
                continue;
            }

            $method->setAccessible(true);
            $result = $method->invoke($extension);

            // Validate that the method returns a closure
            if (!$result instanceof Closure) {
                throw new BadMethodCallException('Method ' . $method->name . ' in extension ' . get_class($extension) . ' must return a Closure.');
            }

            self::$extensions[] = [
                'name' => $method->name,
                'function' => $result,
                'priority' => max(0, min(1000, $priority)) // Ensure priority is between 0-1000
            ];
        }

        // Clear cache when adding new extensions
        static::$extensionCache = [];
    }

    /**
     * Clears all registered extensions.
     */
    public static function clearExtensions(): void
    {
        static::$extensions = [];
        static::$extensionCache = [];
    }

    /**
     * Returns a list of all registered extension names.
     *
     * @return array
     */
    public static function getExtensions(): array
    {
        $names = [];
        foreach (static::$extensions as $ext) {
            $names[] = $ext['name'];
        }
        return array_unique($names);
    }

    public function hasExtension($name): bool
    {
        if (!isset(static::$extensionCache[$name])) {
            $this->buildExtensionCache($name);
        }

        return !empty(static::$extensionCache[$name]);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function pipe($name, ...$arguments)
    {
        // Build cache if needed
        if (!isset(static::$extensionCache[$name])) {
            $this->buildExtensionCache($name);
        }

        foreach (static::$extensionCache[$name] as $function) {
            $return = call_user_func_array($function->bindTo($this, static::class), $arguments);
            if ($return !== null) {
                return $return;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function pipeFalse($name, ...$arguments): bool
    {
        // Build cache if needed
        if (!isset(static::$extensionCache[$name])) {
            $this->buildExtensionCache($name);
        }

        foreach (static::$extensionCache[$name] as $function) {
            $return = call_user_func_array($function->bindTo($this, static::class), $arguments);
            if ($return === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes all extensions with the specified name.
     *
     * @param string $name
     * @return bool True if at least one extension was removed
     */
    public static function removeExtension(string $name): bool
    {
        $originalCount = count(static::$extensions);

        static::$extensions = array_filter(static::$extensions, function ($ext) use ($name) {
            return $ext['name'] !== $name;
        });

        $removed = count(static::$extensions) < $originalCount;

        // Clear cache when removing extensions
        if ($removed) {
            unset(static::$extensionCache[$name]);
        }

        return $removed;
    }

    /**
     * Builds and caches sorted extensions for a method name.
     *
     * @param string $name
     */
    private function buildExtensionCache(string $name): void
    {
        // Filter extensions by name
        $extensions = array_filter(static::$extensions, function ($ext) use ($name) {
            return $ext['name'] === $name;
        });

        // Sort by priority (higher priority first)
        usort($extensions, function ($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // Store sorted functions in cache
        static::$extensionCache[$name] = array_column($extensions, 'function');
    }
}
