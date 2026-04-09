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

use Exception;
use FacturaScripts\Core\KernelException;
use PDO;
use PDOException;
use FacturaScripts\Core\Tools;

/**
 * Class to connect with SQLite.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class SqliteEngine extends DataBaseEngine
{
    /**
     * Link to the SQL statements for the connected database.
     *
     * @var DataBaseQueries
     */
    private $utilsSQL;

    public function __construct()
    {
        parent::__construct();
        $this->utilsSQL = new SqliteQueries();
    }

    public function beginTransaction($link): bool
    {
        return $link->inTransaction() ? true : $link->beginTransaction();
    }

    public function castInteger($link, $column): string
    {
        return 'CAST(' . $this->escapeColumn($link, $column) . ' AS INTEGER)';
    }

    public function close($link): bool
    {
        if ($link->inTransaction()) {
            $link->rollBack();
        }

        return true;
    }

    public function random(): string
    {
        return 'RANDOM()';
    }

    public function columnFromData($colData): array
    {
        $result = [
            'name' => $colData['name'],
            'type' => strtolower($colData['type'] ?? 'text'),
            'default' => $this->normalizeDefaultValue($colData['dflt_value'] ?? null),
            'is_nullable' => !empty($colData['notnull']) ? 'NO' : 'YES',
            'extra' => null,
        ];

        if (!empty($colData['pk']) && $result['type'] === 'integer') {
            $result['type'] = 'serial';
            $result['is_nullable'] = 'NO';
        }

        return $result;
    }

    public function commit($link): bool
    {
        return false === $link->inTransaction() || $link->commit();
    }

    public function compareDataTypes($dbType, $xmlType): bool
    {
        if (parent::compareDataTypes($dbType, $xmlType)) {
            return true;
        }

        $dbType = strtolower((string)$dbType);
        $xmlType = strtolower((string)$xmlType);

        if ($dbType === $xmlType) {
            return true;
        }

        if ($dbType === 'integer' && (in_array($xmlType, ['int', 'integer', 'int2', 'int4', 'int8'], true) || $xmlType === 'boolean')) {
            return true;
        }

        if ($dbType === 'real' && $xmlType === 'double precision') {
            return true;
        }

        if (str_starts_with($dbType, 'varchar(') && str_starts_with($xmlType, 'character varying(')) {
            return substr($dbType, 8, -1) === substr($xmlType, 18, -1);
        }

        if (str_starts_with($dbType, 'character varying(') && str_starts_with($xmlType, 'character varying(')) {
            return substr($dbType, 18, -1) === substr($xmlType, 18, -1);
        }

        return false;
    }

    public function connect(&$error)
    {
        if (false === class_exists('PDO') || false === in_array('sqlite', PDO::getAvailableDrivers(), false)) {
            $error = $this->i18n->trans('php-extension-not-found', ['%extension%' => 'pdo_sqlite']);
            throw new KernelException('DatabaseError', $error);
        }

        $database = self::getDatabasePath(Tools::config('db_name'));
        $directory = dirname($database);
        if ($database !== ':memory:' && false === is_dir($directory) && false === @mkdir($directory, 0755, true)) {
            $error = 'Unable to create SQLite directory: ' . $directory;
            throw new KernelException('DatabaseError', $error);
        }

        try {
            $result = new PDO('sqlite:' . $database);
            $result->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $result->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $result->sqliteCreateFunction('regexp', function ($pattern, $value) {
                if ($value === null) {
                    return 0;
                }

                $regex = '/' . str_replace('/', '\/', (string)$pattern) . '/u';
                return @preg_match($regex, (string)$value) ? 1 : 0;
            }, 2);
            $result->exec('PRAGMA foreign_keys = ' . (Tools::config('db_foreign_keys', true) ? 'ON' : 'OFF') . ';');
            return $result;
        } catch (PDOException $err) {
            $error = $err->getMessage();
            $this->lastErrorMsg = $error;
            throw new KernelException('DatabaseError', $error);
        }
    }

    public function errorMessage($link): string
    {
        if ($this->lastErrorMsg !== '') {
            return $this->lastErrorMsg;
        }

        $error = $link->errorInfo();
        return isset($error[2]) ? (string)$error[2] : '';
    }

    public function escapeColumn($link, $name): string
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            return '"' . implode('"."', $parts) . '"';
        }

        return '"' . $name . '"';
    }

    public function escapeString($link, $str): string
    {
        $quoted = $link->quote($str);
        return substr($quoted, 1, -1);
    }

    public function exec($link, $sql): bool
    {
        $this->lastErrorMsg = '';

        try {
            $sql = trim($sql);
            if ($sql === '') {
                return true;
            }

            // si no hay punto y coma interior, ejecutamos directamente
            $stripped = rtrim($sql, '; ');
            if (strpos($stripped, ';') === false) {
                $link->exec($stripped);
                return true;
            }

            foreach ($this->splitStatements($sql) as $statement) {
                if ($statement === '') {
                    continue;
                }

                $link->exec($statement);
            }

            return true;
        } catch (Exception $err) {
            $this->lastErrorMsg = $err->getMessage();
            return false;
        }
    }

    public function getSQL()
    {
        return $this->utilsSQL;
    }

    public function inTransaction($link): bool
    {
        return $link->inTransaction();
    }

    public function listTables($link): array
    {
        $tables = [];
        foreach ($this->select($link, "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC;") as $row) {
            $tables[] = $row['name'];
        }

        return $tables;
    }

    public function rollback($link): bool
    {
        return false === $link->inTransaction() || $link->rollBack();
    }

    public function select($link, $sql): array
    {
        $this->lastErrorMsg = '';

        try {
            $statement = $link->query($sql);
            return false === $statement ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $err) {
            $this->lastErrorMsg = $err->getMessage();
            return [];
        }
    }

    public function version($link): string
    {
        $data = $this->select($link, 'SELECT sqlite_version() AS version;');
        return empty($data) ? 'SQLITE' : 'SQLITE ' . $data[0]['version'];
    }

    public static function getDatabasePath(?string $database): string
    {
        if (empty($database)) {
            return Tools::folder('MyFiles', 'facturascripts.sqlite');
        }

        if (strpos($database, "\0") !== false) {
            throw new KernelException('DatabaseError', 'Invalid SQLite database path.');
        }

        if ($database === ':memory:' || str_starts_with($database, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $database)) {
            return $database;
        }

        if (str_contains($database, '..')) {
            throw new KernelException('DatabaseError', 'Invalid SQLite database path.');
        }

        return FS_FOLDER . DIRECTORY_SEPARATOR . ltrim($database, DIRECTORY_SEPARATOR);
    }

    private function normalizeDefaultValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === 'NULL') {
            return null;
        }

        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) || (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            return substr($value, 1, -1);
        }

        return strtolower($value) === 'false' || strtolower($value) === 'true' ? strtolower($value) : $value;
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $statement .= $char;

            if ($char === "'" && false === $inDoubleQuote) {
                if ($inSingleQuote && $i + 1 < $length && $sql[$i + 1] === "'") {
                    $statement .= $sql[++$i];
                    continue;
                }

                $inSingleQuote = false === $inSingleQuote;
                continue;
            }

            if ($char === '"' && false === $inSingleQuote) {
                if ($inDoubleQuote && $i + 1 < $length && $sql[$i + 1] === '"') {
                    $statement .= $sql[++$i];
                    continue;
                }

                $inDoubleQuote = false === $inDoubleQuote;
                continue;
            }

            if ($char === ';' && false === $inSingleQuote && false === $inDoubleQuote) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $statements[] = rtrim($statement, ';');
                }

                $statement = '';
            }
        }

        $statement = trim($statement);
        if ($statement !== '') {
            $statements[] = rtrim($statement, ';');
        }

        return $statements;
    }
}
