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

use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;
use PDO;
use PDOException;
use Throwable;

/**
 * Conexión a una base de datos MySQL/MariaDB, PostgreSQL o SQLite mediante PDO.
 *
 * Se crea a partir de un array de parámetros (id, type, host, port, name, user, pass,
 * charset, socket, sslmode, endpoint, options) o de una url del tipo
 * driver://usuario:contraseña@host:puerto/base_de_datos?opciones, como las que dan
 * neon.tech, planetscale o aws.
 *
 * La conexión no se abre al crear el objeto: hay que llamar a connect(). Los métodos
 * select() y exec() admiten parámetros para ejecutar consultas preparadas.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class Connection
{
    /** @var string Canal del log donde se registran las consultas y los errores. */
    const CHANNEL = 'database';

    /** @var string Tipo de motor MySQL o MariaDB. */
    const TYPE_MYSQL = 'mysql';

    /** @var string Tipo de motor PostgreSQL. */
    const TYPE_POSTGRESQL = 'postgresql';

    /** @var string Tipo de motor SQLite. */
    const TYPE_SQLITE = 'sqlite';

    /** @var string Último mensaje de error de la base de datos. */
    private $lastError = '';

    /** @var array Parámetros normalizados de la conexión. */
    private $params;

    /** @var PDO|null Objeto PDO de la conexión, null si no está conectada. */
    private $pdo;

    /** @var array|null Caché con la lista de tablas de la base de datos. */
    private $tables;

    /**
     * Crea la conexión a partir de los parámetros indicados. Si se incluye la clave url,
     * sus valores se usan para completar los que no se hayan indicado explícitamente.
     *
     * @param array $params Parámetros de la conexión.
     */
    public function __construct(array $params)
    {
        if (!empty($params['url'])) {
            $params = array_merge(self::parseUrl($params['url']), array_filter($params, function ($value) {
                return $value !== null && $value !== '';
            }));
        }

        $this->params = self::normalizeParams($params);
    }

    /**
     * Cierra la conexión al destruir el objeto, descartando las transacciones abiertas.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Inicia una transacción. Si ya hay una abierta, no hace nada.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            return true;
        }

        return $this->run(function (PDO $pdo) {
            return $pdo->beginTransaction();
        });
    }

    /**
     * Cierra la conexión. Si hay una transacción abierta, la descarta.
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->inTransaction()) {
            $this->rollback();
        }

        $this->pdo = null;
        $this->tables = null;
        return true;
    }

    /**
     * Confirma la transacción abierta.
     *
     * @return bool
     */
    public function commit(): bool
    {
        if (false === $this->inTransaction()) {
            return false;
        }

        return $this->run(function (PDO $pdo) {
            return $pdo->commit();
        });
    }

    /**
     * Abre la conexión con la base de datos. Devuelve true si ya estaba conectada.
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->connected()) {
            return true;
        }

        $this->lastError = '';
        $driver = $this->driver();
        if (false === in_array($driver, PDO::getAvailableDrivers(), true)) {
            $this->setError('PDO driver not found: pdo_' . $driver);
            return false;
        }

        try {
            if ($this->type() === self::TYPE_SQLITE && false === $this->checkSqliteFolder()) {
                return false;
            }

            $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ] + $this->params['options'];

            // desde PHP 8.4, PDO::connect() devuelve la subclase del driver (Pdo\Sqlite...)
            $this->pdo = method_exists(PDO::class, 'connect') ?
                PDO::connect($this->dsn(), $this->params['user'], $this->params['pass'], $options) :
                new PDO($this->dsn(), $this->params['user'], $this->params['pass'], $options);
            $this->afterConnect();
        } catch (Throwable $exception) {
            $this->pdo = null;
            $this->setError($exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Devuelve true si la conexión está abierta.
     *
     * @return bool
     */
    public function connected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Escapa el nombre de una columna (o tabla.columna) con las comillas de su motor,
     * duplicando las comillas que contenga para evitar inyecciones SQL.
     *
     * @param string $name
     *
     * @return string
     */
    public function escapeColumn(string $name): string
    {
        $quote = $this->type() === self::TYPE_MYSQL ? '`' : '"';
        $parts = array_map(function (string $part) use ($quote) {
            return $quote . str_replace($quote, $quote . $quote, $part) . $quote;
        }, explode('.', $name));

        return implode('.', $parts);
    }

    /**
     * Escapa un texto para usarlo dentro de una consulta, sin añadir las comillas exteriores.
     *
     * @param string $str
     *
     * @return string
     */
    public function escapeString(string $str): string
    {
        if (false === $this->connect()) {
            return str_replace("'", "''", $str);
        }

        return substr($this->pdo->quote($str), 1, -1);
    }

    /**
     * Ejecuta una o varias sentencias que no devuelven filas (insert, update, delete, create...).
     * Si se indican parámetros, se ejecuta como consulta preparada (una sola sentencia).
     *
     * @param string $sql
     * @param array $params Valores para los marcadores ? o :nombre de la sentencia.
     *
     * @return bool
     */
    public function exec(string $sql, array $params = []): bool
    {
        // la lista de tablas puede cambiar al ejecutar la sentencia
        $this->tables = null;

        return $this->run(function (PDO $pdo) use ($sql, $params) {
            if (empty($params)) {
                $pdo->exec($sql);
                return true;
            }

            return $pdo->prepare($sql)->execute($params);
        }, $sql);
    }

    /**
     * Devuelve el identificador de la conexión.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->params['id'];
    }

    /**
     * Indica si hay una transacción abierta.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo !== null && $this->pdo->inTransaction();
    }

    /**
     * Devuelve el último id asignado por un INSERT, o 0 si no se puede obtener.
     *
     * @return int
     */
    public function lastInsertId(): int
    {
        if (false === $this->connected()) {
            return 0;
        }

        try {
            return (int)$this->pdo->lastInsertId();
        } catch (Throwable $exception) {
            // PostgreSQL lanza una excepción si no se ha usado ninguna secuencia en la sesión
            return 0;
        }
    }

    /**
     * Devuelve el último mensaje de error, o una cadena vacía si no lo hay.
     *
     * @return string
     */
    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * Devuelve el nombre de la base de datos (o la ruta del archivo, en SQLite).
     *
     * @return string
     */
    public function name(): string
    {
        return $this->params['name'];
    }

    /**
     * Devuelve los parámetros normalizados de la conexión, sin la contraseña.
     *
     * @return array
     */
    public function params(): array
    {
        $params = $this->params;
        unset($params['pass']);
        return $params;
    }

    /**
     * Devuelve el objeto PDO de la conexión, conectando si es necesario. Null si no se puede conectar.
     *
     * @return PDO|null
     */
    public function pdo(): ?PDO
    {
        return $this->connect() ? $this->pdo : null;
    }

    /**
     * Descarta la transacción abierta.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        if (false === $this->inTransaction()) {
            return false;
        }

        return $this->run(function (PDO $pdo) {
            return $pdo->rollBack();
        });
    }

    /**
     * Ejecuta una consulta y devuelve sus filas como arrays asociativos,
     * o un array vacío si falla. Admite parámetros para consultas preparadas.
     *
     * @param string $sql
     * @param array $params Valores para los marcadores ? o :nombre de la consulta.
     *
     * @return array
     */
    public function select(string $sql, array $params = []): array
    {
        $rows = [];
        $this->run(function (PDO $pdo) use ($sql, $params, &$rows) {
            if (empty($params)) {
                $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                return true;
            }

            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }, $sql);

        return $rows;
    }

    /**
     * Ejecuta una consulta paginada: devuelve como máximo $limit filas a partir de la $offset.
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @param array $params
     *
     * @return array
     */
    public function selectLimit(string $sql, int $limit, int $offset = 0, array $params = []): array
    {
        if ($limit > 0) {
            $sql = rtrim(trim($sql), ';') . ' LIMIT ' . $limit . ' OFFSET ' . max(0, $offset);
        }

        return $this->select($sql, $params);
    }

    /**
     * Indica si existe la tabla en la base de datos.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        return in_array($tableName, $this->tables(), true);
    }

    /**
     * Devuelve la lista de tablas de la base de datos.
     *
     * @return array
     */
    public function tables(): array
    {
        if ($this->tables !== null) {
            return $this->tables;
        }

        switch ($this->type()) {
            case self::TYPE_MYSQL:
                $sql = 'SHOW TABLES;';
                break;

            case self::TYPE_POSTGRESQL:
                $sql = "SELECT tablename FROM pg_catalog.pg_tables"
                    . " WHERE schemaname NOT IN ('pg_catalog','information_schema') ORDER BY 1 ASC;";
                break;

            default:
                $sql = "SELECT name FROM sqlite_master WHERE type = 'table'"
                    . " AND name NOT LIKE 'sqlite_%' ORDER BY 1 ASC;";
                break;
        }

        $tables = [];
        foreach ($this->select($sql) as $row) {
            $tables[] = (string)reset($row);
        }

        // solo guardamos la caché si hay conexión, para volver a intentarlo más tarde
        if ($this->connected()) {
            $this->tables = $tables;
        }

        return $tables;
    }

    /**
     * Devuelve el tipo de motor: mysql, postgresql o sqlite.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->params['type'];
    }

    /**
     * Convierte un valor en un literal válido para una consulta SQL.
     * Las fechas d-m-Y (con o sin hora) se convierten al formato Y-m-d de la base de datos.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function var2str($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_array($value) || is_object($value)) {
            throw new KernelException('DatabaseError', 'Array or object not allowed in var2str function');
        }

        // en SQLite los números van sin comillas, porque una expresión sin afinidad de tipo
        // (como LENGTH(x)) no se puede comparar con un texto como '10'
        if (is_int($value) || is_float($value)) {
            return $this->type() === self::TYPE_SQLITE ? (string)$value : "'" . $value . "'";
        }

        // fechas en formato d-m-Y, con o sin hora
        $value = (string)$value;
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})( (\d{1,2}):(\d{1,2}):(\d{1,2}))?$/', $value, $matches)) {
            $format = empty($matches[4]) ? 'Y-m-d' : 'Y-m-d H:i:s';
            $date = date($format, strtotime($value));
            if (false === str_starts_with($date, '1970-01-01')) {
                return "'" . $date . "'";
            }
        }

        return "'" . $this->escapeString($value) . "'";
    }

    /**
     * Devuelve la versión del servidor de base de datos, precedida del nombre del motor.
     *
     * @return string
     */
    public function version(): string
    {
        if (false === $this->connect()) {
            return '';
        }

        $names = [
            self::TYPE_MYSQL => 'MySQL',
            self::TYPE_POSTGRESQL => 'PostgreSQL',
            self::TYPE_SQLITE => 'SQLite',
        ];

        return $names[$this->type()] . ' ' . $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Normaliza el nombre del tipo de motor: mysql, postgresql o sqlite.
     * Acepta los alias habituales de las urls (mariadb, postgres, pgsql, sqlite3...).
     *
     * @param string $type
     *
     * @return string
     */
    public static function normalizeType(string $type): string
    {
        switch (strtolower(trim($type))) {
            case 'postgres':
            case 'postgresql':
            case 'pgsql':
                return self::TYPE_POSTGRESQL;

            case 'sqlite':
            case 'sqlite3':
                return self::TYPE_SQLITE;

            default:
                return self::TYPE_MYSQL;
        }
    }

    /**
     * Convierte una url del tipo driver://usuario:contraseña@host:puerto/base_de_datos?opciones
     * en un array de parámetros de conexión. Para SQLite la url es sqlite://ruta/al/archivo
     * (ruta relativa a la carpeta de FacturaScripts), sqlite:///ruta/absoluta o sqlite://:memory:.
     *
     * Opciones reconocidas: sslmode, endpoint (PostgreSQL), charset y socket (MySQL).
     *
     * @param string $url
     *
     * @return array
     */
    public static function parseUrl(string $url): array
    {
        $url = trim($url);
        $schemePos = strpos($url, '://');
        if ($schemePos === false) {
            return [];
        }

        $type = self::normalizeType(substr($url, 0, $schemePos));
        $rest = substr($url, $schemePos + 3);

        // separamos las opciones de la query string
        $options = [];
        $queryPos = strpos($rest, '?');
        if ($queryPos !== false) {
            parse_str(substr($rest, $queryPos + 1), $options);
            $rest = substr($rest, 0, $queryPos);
        }

        if ($type === self::TYPE_SQLITE) {
            return array_merge($options, [
                'type' => $type,
                'name' => rawurldecode($rest),
            ]);
        }

        $parts = parse_url('scheme://' . $rest);
        if ($parts === false) {
            return [];
        }

        return array_merge($options, array_filter([
            'type' => $type,
            'host' => isset($parts['host']) ? rawurldecode($parts['host']) : null,
            'port' => $parts['port'] ?? null,
            'name' => isset($parts['path']) ? rawurldecode(ltrim($parts['path'], '/')) : null,
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'pass' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    /**
     * Registra en SQLite las funciones de MySQL y PostgreSQL que usan las consultas
     * de FacturaScripts y que SQLite no tiene, y activa las claves ajenas.
     */
    private function afterConnect(): void
    {
        switch ($this->type()) {
            case self::TYPE_POSTGRESQL:
                $this->pdo->exec('SET DATESTYLE TO ISO, YMD;');
                break;

            case self::TYPE_MYSQL:
                if (false === $this->params['foreign_keys']) {
                    $this->pdo->exec('SET foreign_key_checks = 0;');
                }
                break;

            case self::TYPE_SQLITE:
                $this->pdo->exec('PRAGMA foreign_keys = ' . ($this->params['foreign_keys'] ? 'ON' : 'OFF') . ';');
                $this->pdo->exec('PRAGMA busy_timeout = 5000;');
                SqliteFunctions::register($this->pdo);
                break;
        }
    }

    /**
     * Comprueba que existe la carpeta del archivo SQLite y, si no, la crea.
     *
     * @return bool
     */
    private function checkSqliteFolder(): bool
    {
        $file = $this->params['name'];
        if ($file === ':memory:') {
            return true;
        }

        $folder = dirname($file);
        if (is_dir($folder) || @mkdir($folder, 0755, true)) {
            return true;
        }

        $this->setError('Unable to create the folder ' . $folder);
        return false;
    }

    /**
     * Devuelve el nombre del driver PDO del motor.
     *
     * @return string
     */
    private function driver(): string
    {
        $drivers = [
            self::TYPE_MYSQL => 'mysql',
            self::TYPE_POSTGRESQL => 'pgsql',
            self::TYPE_SQLITE => 'sqlite',
        ];

        return $drivers[$this->type()];
    }

    /**
     * Devuelve el DSN de PDO de la conexión.
     *
     * @return string
     */
    private function dsn(): string
    {
        $params = $this->params;
        switch ($this->type()) {
            case self::TYPE_SQLITE:
                return 'sqlite:' . $params['name'];

            case self::TYPE_POSTGRESQL:
                $dsn = 'pgsql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['name'];
                if ($params['sslmode'] !== '') {
                    $dsn .= ';sslmode=' . $params['sslmode'];
                }
                if ($params['endpoint'] !== '') {
                    $dsn .= ";options='endpoint=" . $params['endpoint'] . "'";
                }
                return $dsn;

            default:
                $dsn = $params['socket'] !== '' ?
                    'mysql:unix_socket=' . $params['socket'] :
                    'mysql:host=' . $params['host'] . ';port=' . $params['port'];
                if ($params['name'] !== '') {
                    $dsn .= ';dbname=' . $params['name'];
                }
                return $dsn . ';charset=' . $params['charset'];
        }
    }

    /**
     * Completa los parámetros con los valores por defecto de cada motor.
     *
     * @param array $params
     *
     * @return array
     */
    private static function normalizeParams(array $params): array
    {
        $type = self::normalizeType((string)($params['type'] ?? self::TYPE_MYSQL));
        $defaultPorts = [self::TYPE_MYSQL => 3306, self::TYPE_POSTGRESQL => 5432, self::TYPE_SQLITE => 0];

        $result = [
            'id' => (string)($params['id'] ?? ''),
            'type' => $type,
            'host' => (string)($params['host'] ?? 'localhost'),
            'port' => (int)($params['port'] ?? 0) ?: $defaultPorts[$type],
            'name' => (string)($params['name'] ?? ''),
            'user' => (string)($params['user'] ?? ''),
            'pass' => (string)($params['pass'] ?? ''),
            'charset' => (string)($params['charset'] ?? 'utf8'),
            'socket' => (string)($params['socket'] ?? ''),
            'sslmode' => (string)($params['sslmode'] ?? ''),
            'endpoint' => (string)($params['endpoint'] ?? ''),
            'foreign_keys' => filter_var($params['foreign_keys'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'options' => is_array($params['options'] ?? null) ? $params['options'] : [],
        ];

        if ($result['id'] === '') {
            $result['id'] = $type . ':' . $result['host'] . ':' . $result['port'] . '/' . $result['name'];
        }

        // en SQLite el nombre es la ruta del archivo: si es relativa, partimos de la carpeta de FacturaScripts
        if ($type === self::TYPE_SQLITE && $result['name'] !== ':memory:' && $result['name'] !== '') {
            $isAbsolute = str_starts_with($result['name'], '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $result['name']);
            if (!$isAbsolute) {
                $result['name'] = Tools::folder($result['name']);
            }
        }

        return $result;
    }

    /**
     * Ejecuta la función indicada con el objeto PDO, conectando si es necesario,
     * capturando las excepciones y registrando la consulta y los errores en el log.
     *
     * @param callable $callback
     * @param string $sql Consulta que se registra en el log, si la hay.
     *
     * @return bool
     */
    private function run(callable $callback, string $sql = ''): bool
    {
        if (false === $this->connect()) {
            return false;
        }

        $this->lastError = '';
        $start = microtime(true);

        try {
            $result = (bool)$callback($this->pdo);
        } catch (PDOException $exception) {
            $this->setError($exception->getMessage(), $sql);
            return false;
        }

        if ($sql !== '') {
            Tools::log(self::CHANNEL)->debug($sql, ['duration' => microtime(true) - $start]);
        }

        return $result;
    }

    /**
     * Guarda el último error y lo registra en el log.
     *
     * @param string $message
     * @param string $sql
     */
    private function setError(string $message, string $sql = ''): void
    {
        $this->lastError = $message;
        Tools::log(self::CHANNEL)->error($message, $sql === '' ? [] : ['sql' => $sql]);
    }
}
