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

namespace FacturaScripts\Core;

use FacturaScripts\Core\DB\Connection;

/**
 * Acceso a la base de datos mediante PDO, con soporte para MySQL/MariaDB, PostgreSQL y SQLite.
 *
 * Los métodos estáticos trabajan con la base de datos configurada en FacturaScripts, que se lee
 * con Tools::config(): la constante FS_DB_URL (driver://usuario:contraseña@host:puerto/bd?opciones)
 * o las constantes FS_DB_TYPE, FS_DB_HOST, FS_DB_PORT, FS_DB_NAME, FS_DB_USER y FS_DB_PASS.
 * La conexión se abre la primera vez que se usa.
 *
 * Para trabajar con otras bases de datos se crea una conexión nueva con newConnection(),
 * que queda registrada con su id para recuperarla después con connection($id):
 *
 *     $nueva = DB::newConnection(['id' => 'nueva', 'type' => 'mysql', 'host' => ...]);
 *     if ($nueva->connect()) {
 *         $familias = DB::connection('nueva')->select('SELECT * FROM familias');
 *     }
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
final class DB
{
    /** @var string Id de la conexión a la base de datos de FacturaScripts. */
    const DEFAULT_CONNECTION = 'default';

    /** @var Connection[] Conexiones registradas, indexadas por su id. */
    private static $connections = [];

    /**
     * Inicia una transacción en la base de datos de FacturaScripts.
     *
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    /**
     * Cierra la conexión indicada (por defecto, la de FacturaScripts) y la elimina del registro.
     *
     * @param string $id
     *
     * @return bool
     */
    public static function close(string $id = self::DEFAULT_CONNECTION): bool
    {
        if (false === isset(self::$connections[$id])) {
            return true;
        }

        $result = self::$connections[$id]->close();
        unset(self::$connections[$id]);
        return $result;
    }

    /**
     * Confirma la transacción abierta en la base de datos de FacturaScripts.
     *
     * @return bool
     */
    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    /**
     * Devuelve los parámetros de conexión de la base de datos de FacturaScripts,
     * leídos de la configuración (FS_DB_URL o las constantes FS_DB_*).
     *
     * @return array
     */
    public static function configParams(): array
    {
        // con FS_DB_URL, la url manda y el resto de constantes solo completan lo que no indique
        $url = (string)Tools::config('db_url', '');
        $params = [
            'id' => self::DEFAULT_CONNECTION,
            'url' => $url,
            'type' => $url === '' ? Tools::config('db_type', Connection::TYPE_MYSQL) : null,
            'host' => $url === '' ? Tools::config('db_host', 'localhost') : null,
            'port' => $url === '' ? Tools::config('db_port') : null,
            'name' => $url === '' ? Tools::config('db_name', '') : null,
            'user' => $url === '' ? Tools::config('db_user', '') : null,
            'pass' => $url === '' ? Tools::config('db_pass', '') : null,
            'charset' => Tools::config('mysql_charset'),
            'socket' => Tools::config('mysql_socket'),
            'sslmode' => Tools::config('pgsql_ssl'),
            'endpoint' => Tools::config('pgsql_endpoint'),
            'foreign_keys' => Tools::config('db_foreign_keys', true),
        ];

        return array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Indica si la base de datos de FacturaScripts está conectada. Si aún no se ha usado,
     * intenta conectar.
     *
     * @return bool
     */
    public static function connected(): bool
    {
        return self::connection()->connect();
    }

    /**
     * Devuelve la conexión con el id indicado. La conexión de FacturaScripts (por defecto)
     * se crea a partir de la configuración la primera vez que se pide.
     *
     * @param string $id
     *
     * @return Connection
     * @throws KernelException si no existe ninguna conexión con ese id.
     */
    public static function connection(string $id = self::DEFAULT_CONNECTION): Connection
    {
        if (isset(self::$connections[$id])) {
            return self::$connections[$id];
        }

        if ($id !== self::DEFAULT_CONNECTION) {
            throw new KernelException('DatabaseError', 'Database connection not found: ' . $id);
        }

        self::$connections[$id] = new Connection(self::configParams());
        return self::$connections[$id];
    }

    /**
     * Devuelve los ids de las conexiones registradas.
     *
     * @return string[]
     */
    public static function connections(): array
    {
        return array_keys(self::$connections);
    }

    /**
     * Escapa el nombre de una columna para la base de datos de FacturaScripts.
     *
     * @param string $name
     *
     * @return string
     */
    public static function escapeColumn(string $name): string
    {
        return self::connection()->escapeColumn($name);
    }

    /**
     * Ejecuta sentencias que no devuelven filas en la base de datos de FacturaScripts.
     *
     * @param string $sql
     * @param array $params Valores para los marcadores de la consulta preparada.
     *
     * @return bool
     */
    public static function exec(string $sql, array $params = []): bool
    {
        return self::connection()->exec($sql, $params);
    }

    /**
     * Indica si hay una transacción abierta en la base de datos de FacturaScripts.
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }

    /**
     * Devuelve el último id asignado por un INSERT en la base de datos de FacturaScripts.
     *
     * @return int
     */
    public static function lastInsertId(): int
    {
        return self::connection()->lastInsertId();
    }

    /**
     * Devuelve el último error de la base de datos de FacturaScripts.
     *
     * @return string
     */
    public static function lastError(): string
    {
        return self::connection()->lastError();
    }

    /**
     * Crea una conexión a otra base de datos y la registra con su id (parámetro id), para poder
     * recuperarla después con connection($id). Si ya había una conexión con ese id, la sustituye.
     * La conexión no se abre: hay que llamar a connect().
     *
     * Parámetros: id, type (mysql, postgresql o sqlite), host, port, name, user, pass, charset,
     * socket, sslmode, endpoint, foreign_keys y options (atributos de PDO), o url.
     *
     * @param array $params
     *
     * @return Connection
     */
    public static function newConnection(array $params): Connection
    {
        $connection = new Connection($params);
        $id = $connection->id();
        if (isset(self::$connections[$id]) && self::$connections[$id] !== $connection) {
            self::$connections[$id]->close();
        }

        self::$connections[$id] = $connection;
        return $connection;
    }

    /**
     * Descarta la transacción abierta en la base de datos de FacturaScripts.
     *
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::connection()->rollback();
    }

    /**
     * Ejecuta una consulta en la base de datos de FacturaScripts y devuelve sus filas.
     *
     * @param string $sql
     * @param array $params Valores para los marcadores de la consulta preparada.
     *
     * @return array
     */
    public static function select(string $sql, array $params = []): array
    {
        return self::connection()->select($sql, $params);
    }

    /**
     * Ejecuta una consulta paginada en la base de datos de FacturaScripts.
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @param array $params
     *
     * @return array
     */
    public static function selectLimit(string $sql, int $limit, int $offset = 0, array $params = []): array
    {
        return self::connection()->selectLimit($sql, $limit, $offset, $params);
    }

    /**
     * Indica si existe la tabla en la base de datos de FacturaScripts.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public static function tableExists(string $tableName): bool
    {
        return self::connection()->tableExists($tableName);
    }

    /**
     * Devuelve la lista de tablas de la base de datos de FacturaScripts.
     *
     * @return array
     */
    public static function tables(): array
    {
        return self::connection()->tables();
    }

    /**
     * Devuelve el tipo de motor de la base de datos de FacturaScripts: mysql, postgresql o sqlite.
     *
     * @return string
     */
    public static function type(): string
    {
        return self::connection()->type();
    }

    /**
     * Convierte un valor en un literal SQL válido para la base de datos de FacturaScripts.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function var2str($value): string
    {
        return self::connection()->var2str($value);
    }

    /**
     * Devuelve el motor y la versión de la base de datos de FacturaScripts.
     *
     * @return string
     */
    public static function version(): string
    {
        return self::connection()->version();
    }
}
