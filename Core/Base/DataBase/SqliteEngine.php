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

namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\DB;
use FacturaScripts\Core\KernelException;
use PDO;
use Throwable;

/**
 * Motor SQLite de la clase DataBase. Usa el objeto PDO de la conexión por defecto de
 * Core\DB, de forma que DataBase y DB comparten la misma conexión y las mismas transacciones
 * (en SQLite dos conexiones distintas se bloquearían entre sí al escribir).
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class SqliteEngine extends DataBaseEngine
{
    /** @var SqliteQueries Sentencias SQL del motor. */
    private $utilsSQL;

    /**
     * Inicializa el motor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->utilsSQL = new SqliteQueries();
    }

    /**
     * Inicia una transacción.
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function beginTransaction($link): bool
    {
        return $this->run(function () use ($link) {
            return $link->inTransaction() || $link->beginTransaction();
        });
    }

    /**
     * Devuelve el SQL para convertir una columna a entero.
     *
     * @param PDO $link
     * @param string $column
     *
     * @return string
     */
    public function castInteger($link, $column): string
    {
        return 'CAST(' . $this->escapeColumn($link, $column) . ' AS INTEGER)';
    }

    /**
     * Cierra la conexión de Core\DB.
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function close($link): bool
    {
        return DB::close();
    }

    /**
     * Convierte una fila de pragma_table_info en la estructura de columna que usa DbUpdater.
     *
     * @param array $colData
     *
     * @return array
     */
    public function columnFromData($colData): array
    {
        return [
            'name' => $colData['name'],
            'type' => strtolower((string)$colData['type']),
            'default' => $colData['dflt_value'],
            'is_nullable' => (int)$colData['notnull'] === 1 || (int)$colData['pk'] === 1 ? 'NO' : 'YES',
        ];
    }

    /**
     * Confirma la transacción abierta.
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function commit($link): bool
    {
        return $this->run(function () use ($link) {
            return $link->inTransaction() && $link->commit();
        });
    }

    /**
     * SQLite tiene tipos dinámicos: no hace falta cambiar el tipo de las columnas.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType): bool
    {
        return true;
    }

    /**
     * Conecta con la base de datos mediante la conexión por defecto de Core\DB.
     *
     * @param string $error
     *
     * @return PDO|null
     */
    public function connect(&$error)
    {
        $connection = DB::connection();
        if (false === $connection->connect()) {
            $error = $connection->lastError();
            $this->lastErrorMsg = $error;
            throw new KernelException('DatabaseError', $error);
        }

        return $connection->pdo();
    }

    /**
     * Devuelve el último error.
     *
     * @param PDO $link
     *
     * @return string
     */
    public function errorMessage($link): string
    {
        return $this->lastErrorMsg;
    }

    /**
     * Escapa el nombre de una columna (o tabla.columna) con comillas dobles.
     *
     * @param PDO $link
     * @param string $name
     *
     * @return string
     */
    public function escapeColumn($link, $name): string
    {
        $parts = array_map(function (string $part) {
            return '"' . str_replace('"', '""', $part) . '"';
        }, explode('.', $name));

        return implode('.', $parts);
    }

    /**
     * Escapa las comillas de un texto.
     *
     * @param PDO $link
     * @param string $str
     *
     * @return string
     */
    public function escapeString($link, $str): string
    {
        return str_replace("'", "''", (string)$str);
    }

    /**
     * Ejecuta una o varias sentencias (inserts, updates, deletes...).
     *
     * @param PDO $link
     * @param string $sql
     *
     * @return bool
     */
    public function exec($link, $sql): bool
    {
        return $this->run(function () use ($link, $sql) {
            $link->exec($sql);
            return true;
        });
    }

    /**
     * Devuelve las sentencias SQL del motor.
     *
     * @return SqliteQueries
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }

    /**
     * Indica si hay una transacción abierta.
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function inTransaction($link): bool
    {
        return $link instanceof PDO && $link->inTransaction();
    }

    /**
     * Devuelve la lista de tablas.
     *
     * @param PDO $link
     *
     * @return array
     */
    public function listTables($link): array
    {
        $tables = [];
        $sql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY 1 ASC;";
        foreach ($this->select($link, $sql) as $row) {
            $tables[] = $row['name'];
        }

        return $tables;
    }

    /**
     * Devuelve la función de orden aleatorio.
     *
     * @return string
     */
    public function random(): string
    {
        return 'RANDOM()';
    }

    /**
     * Descarta la transacción abierta.
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function rollback($link): bool
    {
        return $this->run(function () use ($link) {
            return $link->inTransaction() && $link->rollBack();
        });
    }

    /**
     * Ejecuta una consulta y devuelve sus filas, o un array vacío si falla.
     *
     * @param PDO $link
     * @param string $sql
     *
     * @return array
     */
    public function select($link, $sql): array
    {
        $rows = [];
        $this->run(function () use ($link, $sql, &$rows) {
            $rows = $link->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return true;
        });

        return $rows;
    }

    /**
     * Devuelve la versión de SQLite.
     *
     * @param PDO $link
     *
     * @return string
     */
    public function version($link): string
    {
        return 'SQLITE ' . $link->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Ejecuta la función indicada, guardando el mensaje de error si lanza una excepción.
     *
     * @param callable $callback
     *
     * @return bool
     */
    private function run(callable $callback): bool
    {
        $this->lastErrorMsg = '';

        try {
            return (bool)$callback();
        } catch (Throwable $exception) {
            $this->lastErrorMsg = $exception->getMessage();
            return false;
        }
    }
}
