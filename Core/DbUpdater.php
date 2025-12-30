<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseQueries;

/**
 * Actualiza la estructura de la base de datos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class DbUpdater
{
    const CHANGELOG_FILE = 'db-changelog.json';
    const FILE_NAME = 'db-updater.json';

    /** @var array */
    private static $checked_tables;

    /** @var DataBase */
    private static $db;

    /** @var string */
    private static $last_error;

    /** @var DataBaseQueries */
    private static $sql_tool;

    public static function createOrUpdateTable(string $table_name, array $structure = [], string $sql_after = ''): bool
    {
        if (self::db()->tableExists($table_name)) {
            return self::updateTable($table_name, $structure);
        }

        return self::createTable($table_name, $structure, $sql_after);
    }

    public static function createTable(string $table_name, array $structure = [], string $sql_after = ''): bool
    {
        if (self::isTableChecked($table_name)) {
            Tools::log()->warning('Table ' . $table_name . ' already checked');
            return false;
        }

        if (self::db()->tableExists($table_name)) {
            Tools::log()->warning('Table ' . $table_name . ' already exists');
            return false;
        }

        if (empty($structure)) {
            $file_path = self::getTableXmlLocation($table_name);
            $structure = self::readTableXml($file_path);
        }

        $sql = self::sqlTool()->sqlCreateTable($table_name, $structure['columns'], $structure['constraints'], $structure['indexes']) . $sql_after;
        if (false === self::db()->exec($sql)) {
            self::$last_error = 'Error creating table ' . $table_name . ': ' . $sql;
            self::save($table_name);
            self::saveChangeLog('create-table', $sql);
            return false;
        }

        // actualizamos las secuencias (PostgreSQL)
        self::$db->updateSequence($table_name, self::db()->getColumns($table_name));

        self::save($table_name);
        self::saveChangeLog('create-table', $sql);

        Tools::log()->debug('table-checked', ['%tableName%' => $table_name]);

        return true;
    }

    public static function dropTable(string $table_name): bool
    {
        if (false === self::db()->tableExists($table_name)) {
            return false;
        }

        $sql = self::sqlTool()->sqlDropTable($table_name);
        if (self::db()->exec($sql)) {
            self::saveChangeLog('drop-table', $sql);
            Tools::log()->debug('table-deleted', ['%tableName%' => $table_name]);

            self::rebuild();
            return true;
        }

        self::saveChangeLog('drop-table', $sql);

        self::rebuild();
        return false;
    }

    public static function getLastError(): string
    {
        return self::$last_error;
    }

    public static function getTableXmlLocation(string $table_name): string
    {
        $dinFile = Tools::folder('Dinamic', 'Table', $table_name . '.xml');
        $coreFile = Tools::folder('Core', 'Table', $table_name . '.xml');

        return file_exists($dinFile) ? $dinFile : $coreFile;
    }

    public static function isTableChecked(string $table_name): bool
    {
        if (null === self::$checked_tables) {
            // leemos el fichero
            $file = Tools::folder('MyFiles', self::FILE_NAME);
            if (false === file_exists($file)) {
                self::$checked_tables = [];
                return false;
            }

            $file_data = file_get_contents(Tools::folder('MyFiles', self::FILE_NAME));
            self::$checked_tables = json_decode($file_data, true) ?? [];
        }

        return in_array($table_name, self::$checked_tables);
    }

    public static function readTableXml(string $file_path): array
    {
        $structure = [
            'columns' => [],
            'constraints' => [],
            'indexes' => [],
        ];

        if (false === file_exists($file_path)) {
            Tools::log()->critical('file-not-found', ['%fileName%' => $file_path]);
            return $structure;
        }

        $xml = simplexml_load_string(file_get_contents($file_path, true));
        if (false === $xml) {
            Tools::log()->critical('error-reading-file', ['%fileName%' => $file_path]);
            return $structure;
        }

        // si no hay columnas, devolvemos la estructura vacía
        if (false === isset($xml->column)) {
            return $structure;
        }

        foreach ($xml->column as $col) {
            $item = [
                'name' => (string)$col->name,
                'type' => strtolower($col->type),
                'null' => $col->null && strtolower($col->null) === 'no' ? 'NO' : 'YES',
                'default' => (string)$col->default,
                'rename' => (string)$col->rename,
            ];

            if ($col->type == 'serial') {
                $item['null'] = 'NO';
                $item['default'] = null;
            }

            $structure['columns'][$item['name']] = $item;
        }

        if (isset($xml->constraint)) {
            foreach ($xml->constraint as $col) {
                $key = (string)$col->name;

                $structure['constraints'][$key] = [
                    'name' => $key,
                    'constraint' => (string)$col->type
                ];
            }
        }

        if (isset($xml->index)) {
            foreach ($xml->index as $col) {
                $key = (string)$col->name;

                $structure['indexes'][$key] = [
                    'name' => $key,
                    'columns' => (string)$col->columns
                ];
            }
        }

        return $structure;
    }

    public static function rebuild(): void
    {
        self::$checked_tables = [];

        // eliminamos el fichero
        $file = Tools::folder('MyFiles', self::FILE_NAME);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function updateTable(string $table_name, array $structure = []): bool
    {
        if (self::isTableChecked($table_name)) {
            Tools::log()->warning('Table ' . $table_name . ' is already checked');
            return true;
        }

        if (empty($structure)) {
            $file_path = self::getTableXmlLocation($table_name);
            $structure = self::readTableXml($file_path);
        }

        // comparamos las columnas, restricciones y los índices de la tabla con los del XML
        $db_cols = self::db()->getColumns($table_name);
        $db_cons = self::db()->getConstraints($table_name);
        $db_indexes = self::db()->getIndexes($table_name);
        $sql = self::compareColumns($table_name, $structure['columns'], $db_cols) .
            self::compareConstraints($table_name, $structure['constraints'], $db_cons) .
            self::compareIndexes($table_name, $structure['indexes'], $db_indexes);
        if (empty($sql)) {
            self::save($table_name);
            Tools::log()->debug('table-checked', ['%tableName%' => $table_name]);
            return true;
        }

        if (false === self::db()->exec($sql)) {
            self::$last_error = 'Error updating table ' . $table_name . ': ' . $sql;
            self::save($table_name);
            return false;
        }

        self::save($table_name);
        Tools::log()->debug('table-checked', ['%tableName%' => $table_name]);

        return true;
    }

    private static function db(): DataBase
    {
        if (null === self::$db) {
            self::$db = new DataBase();
            self::$db->connect();
        }

        return self::$db;
    }

    private static function compareColumns(string $table_name, array $xml_cols, array $db_cols): string
    {
        $sql = '';

        foreach ($xml_cols as $xml_col) {
            $column = self::searchInArray($db_cols, 'name', $xml_col['name']);
            if (empty($column)) {
                $sql_part = self::needRename($db_cols, $xml_col) ?
                    self::sqlTool()->sqlRenameColumn($table_name, $xml_col['rename'], $xml_col['name']) :
                    self::sqlTool()->sqlAlterAddColumn($table_name, $xml_col);
                $sql .= $sql_part;
                self::saveChangeLog('new-column', $sql_part, $xml_col);
                continue;
            }

            if (false === self::compareDataTypes($column['type'], $xml_col['type'])) {
                $sql_part = self::sqlTool()->sqlAlterModifyColumn($table_name, $xml_col);
                $sql .= $sql_part;
                self::saveChangeLog('change-column-type', $sql_part, ['db' => $column, 'xml' => $xml_col]);
            }

            if (false === self::compareDefaults($column['default'], $xml_col['default'])) {
                $sql_part = self::sqlTool()->sqlAlterColumnDefault($table_name, $xml_col);
                $sql .= $sql_part;
                self::saveChangeLog('change-column-default', $sql_part, ['db' => $column, 'xml' => $xml_col]);
            }

            if ($column['is_nullable'] !== $xml_col['null']) {
                $sql_part = self::sqlTool()->sqlAlterColumnNull($table_name, $xml_col);
                $sql .= $sql_part;
                self::saveChangeLog('change-column-null', $sql_part, ['db' => $column, 'xml' => $xml_col]);
            }
        }

        return $sql;
    }

    private static function compareConstraints(string $table_name, array $xml_cons, array $db_cons): string
    {
        if (empty($xml_cons) || false === Tools::config('db_foreign_keys')) {
            return '';
        }

        // si hay que borrar alguna restricción, es mejor borrar todas
        $delete_cons = false;
        $sql_delete = '';
        $sql_delete_fk = '';

        foreach ($db_cons as $db_con) {
            if ($db_con['type'] === 'PRIMARY KEY') {
                // excluimos las claves primarias
                continue;
            } elseif ($db_con['type'] === 'FOREIGN KEY') {
                // es mejor borrar las claves foráneas antes que el resto
                $sql_delete_fk .= self::sqlTool()->sqlDropConstraint($table_name, $db_con);
            } else {
                $sql_delete .= self::sqlTool()->sqlDropConstraint($table_name, $db_con);
            }

            $column = self::searchInArray($xml_cons, 'name', $db_con['name']);
            if (empty($column)) {
                $delete_cons = true;
            }
        }

        // añadimos las nuevas restricciones
        $sql = '';
        foreach ($xml_cons as $xml_con) {
            // excluimos las claves primarias
            if (0 === strpos($xml_con['constraint'], 'PRIMARY')) {
                continue;
            }

            $column = self::searchInArray($db_cons, 'name', $xml_con['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAddConstraint($table_name, $xml_con['name'], $xml_con['constraint']);
            }
        }

        if (!empty($sql)) {
            self::saveChangeLog('constraints', $sql);
        }

        return $delete_cons ?
            $sql_delete_fk . $sql_delete . $sql :
            $sql;
    }

    private static function compareDataTypes(string $db_type, string $xml_type): bool
    {
        return self::db()->getEngine()->compareDataTypes($db_type, $xml_type);
    }

    private static function compareDefaults($val1, $val2): bool
    {
        // Normalizar valores
        $val1 = self::normalizeDefault($val1);
        $val2 = self::normalizeDefault($val2);

        // null y string vacío se consideran equivalentes
        if (($val1 === null || $val1 === '') && ($val2 === null || $val2 === '')) {
            return true;
        }

        // Comparar valores booleanos en todas sus formas
        $bool1 = self::getBoolValue($val1);
        $bool2 = self::getBoolValue($val2);
        if ($bool1 !== null && $bool2 !== null) {
            return $bool1 === $bool2;
        }

        // Comparación normal (con conversión de tipo)
        return $val1 == $val2;
    }

    private static function compareIndexes(string $table_name, array $xml_indexes, array $db_indexes): string
    {
        // Agregamos fs_ al inicio del 'name'
        // Así la comparación es correcta al buscar los índices
        foreach ($xml_indexes as $key => $value) {
            if (isset($value['name'])) {
                $xml_indexes[$key]['name'] = 'fs_' . $value['name'];
            }
        }

        $sql = '';

        // si no existen índices en el xml, borramos todos lo que existan en la base de datos.
        if (empty($xml_indexes)) {
            foreach ($db_indexes as $db_idx) {
                $sql .= self::sqlTool()->sqlDropIndex($table_name, $db_idx);
            }
            if (!empty($sql)) {
                self::saveChangeLog('drop-all-indexes', $sql);
            }
            return $sql;
        }

        // eliminamos los índices que no estén en el XML
        foreach ($db_indexes as $db_idx) {
            $column = self::searchInArray($xml_indexes, 'name', $db_idx['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlDropIndex($table_name, $db_idx);
            }
        }

        // añadimos los índices que no estén en la base de datos
        foreach ($xml_indexes as $xml_idx) {
            $column = self::searchInArray($db_indexes, 'name', $xml_idx['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAddIndex($table_name, $xml_idx['name'], $xml_idx['columns']);
            }
        }

        if (!empty($sql)) {
            self::saveChangeLog('indexes', $sql);
        }

        return $sql;
    }

    private static function getBoolValue($value): ?bool
    {
        if ($value === true || $value === 1 || $value === '1' || strtolower($value ?? '') === 'true') {
            return true;
        }

        if ($value === false || $value === 0 || $value === '0' || strtolower($value ?? '') === 'false') {
            return false;
        }

        return null;
    }

    private static function needRename(array $db_cols, array $xml_col): bool
    {
        if (empty($xml_col['rename'])) {
            return false;
        }

        // comprobamos si la columna a renombrar existe
        $column = self::searchInArray($db_cols, 'name', $xml_col['rename']);
        return !empty($column);
    }

    private static function normalizeDefault($value)
    {
        if ($value === null) {
            return null;
        }

        // Convertir a string si no lo es
        $value = (string)$value;

        // Eliminar comillas simples al inicio y final
        $value = trim($value, "'\"");

        // Eliminar casteos de PostgreSQL (ej: ::character varying)
        if (strpos($value, '::') !== false) {
            $value = substr($value, 0, strpos($value, '::'));
        }

        return $value === '' ? null : $value;
    }

    private static function save(string $table_name): void
    {
        self::$checked_tables[] = $table_name;

        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));

        file_put_contents(
            Tools::folder('MyFiles', self::FILE_NAME),
            json_encode(self::$checked_tables, JSON_PRETTY_PRINT)
        );
    }

    private static function searchInArray(array $items, string $index, string $value): array
    {
        foreach ($items as $column) {
            if ($column[$index] === $value) {
                return $column;
            }
        }

        return [];
    }

    private static function saveChangeLog(string $reason, string $sql, array $context = []): void
    {
        if (empty($sql)) {
            return;
        }

        // leemos el fichero
        $file = Tools::folder('MyFiles', self::CHANGELOG_FILE);
        $changelog = [];
        if (file_exists($file)) {
            $file_data = file_get_contents($file);
            $changelog = json_decode($file_data, true) ?? [];
        }

        // añadimos la nueva entrada
        $changelog[] = [
            'date' => date('Y-m-d H:i:s'),
            'reason' => $reason,
            'sql' => $sql,
            'context' => $context
        ];

        // guardamos el fichero
        file_put_contents($file, json_encode($changelog, JSON_PRETTY_PRINT));
    }

    private static function sqlTool(): DataBaseQueries
    {
        if (null === self::$sql_tool) {
            self::$sql_tool = self::db()->getEngine()->getSQL();
        }

        return self::$sql_tool;
    }
}
