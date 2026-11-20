<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\DB;

use PDO;

/**
 * Registra en una conexión SQLite las funciones de MySQL y PostgreSQL que usan las consultas
 * de FacturaScripts y que SQLite no incluye (o no incluye en todas sus versiones).
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class SqliteFunctions
{
    /**
     * Registra las funciones en la conexión SQLite indicada.
     *
     * @param PDO $pdo
     */
    public static function register(PDO $pdo): void
    {
        // REGEXP: SQLite reconoce el operador, pero necesita la función regexp(patrón, valor)
        self::create($pdo, 'regexp', [self::class, 'regexp'], 2);

        // CONCAT: solo existe a partir de SQLite 3.44
        self::create($pdo, 'concat', [self::class, 'concat'], -1);

        // NOW: fecha y hora actuales, como en MySQL y PostgreSQL
        self::create($pdo, 'now', [self::class, 'now'], 0);
    }

    /**
     * Registra una función. Desde PHP 8.4 se usa Pdo\Sqlite::createFunction(), porque
     * PDO::sqliteCreateFunction() está obsoleto desde PHP 8.5.
     *
     * @param PDO $pdo
     * @param string $name
     * @param callable $callback
     * @param int $numArgs
     */
    private static function create(PDO $pdo, string $name, callable $callback, int $numArgs): void
    {
        if (method_exists($pdo, 'createFunction')) {
            $pdo->createFunction($name, $callback, $numArgs);
            return;
        }

        $pdo->sqliteCreateFunction($name, $callback, $numArgs);
    }

    /**
     * Concatena los valores recibidos, ignorando los nulos (como CONCAT de PostgreSQL).
     *
     * @param mixed ...$values
     *
     * @return string
     */
    public static function concat(...$values): string
    {
        return implode('', array_map('strval', array_filter($values, function ($value) {
            return $value !== null;
        })));
    }

    /**
     * Devuelve la fecha y hora actuales en formato Y-m-d H:i:s.
     *
     * @return string
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Devuelve 1 si el valor cumple la expresión regular (sin distinguir mayúsculas), 0 si no.
     *
     * @param string|null $pattern
     * @param mixed $value
     *
     * @return int
     */
    public static function regexp(?string $pattern, $value): int
    {
        if ($pattern === null || $value === null) {
            return 0;
        }

        $regex = '/' . str_replace('/', '\/', $pattern) . '/iu';
        return @preg_match($regex, (string)$value) === 1 ? 1 : 0;
    }
}
