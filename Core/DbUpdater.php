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
    const FILE_NAME = 'db-updater.json';

    /** @var array */
    private static $checked_tables;

    /** @var DataBase */
    private static $db;

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
        if (self::db()->exec($sql)) {
            self::save($table_name);
            Tools::log()->debug('table-checked', ['%tableName%' => $table_name]);
            return true;
        }

        Tools::log()->critical('Failed to create table ' . $table_name, ['sql' => $sql]);
        self::save($table_name);
        return false;
    }

    public static function dropTable(string $table_name): bool
    {
        if (false === self::db()->tableExists($table_name)) {
            return false;
        }

        $sql = self::sqlTool()->sqlDropTable($table_name);
        if (self::db()->exec($sql)) {
            self::rebuild();

            Tools::log()->debug('table-deleted', ['%tableName%' => $table_name]);
            return true;
        }

        self::rebuild();
        return false;
    }

    public static function getTableXmlLocation(string $table_name): string
    {
        $fileName = Tools::folder('Dinamic', 'Table', $table_name . '.xml');
        if (Tools::config('debug') && false === file_exists($fileName)) {
            return Tools::folder('Core', 'Table', $table_name . '.xml');
        }

        return $fileName;
    }

    public static function isTableChecked(string $table_name): bool
    {
        if (null === self::$checked_tables) {
            // read the file
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

        // if no column, return empty structure
        if (false === isset($xml->column)) {
            return $structure;
        }

        foreach ($xml->column as $col) {
            $item = [
                'name' => (string)$col->name,
                'type' => (string)$col->type,
                'null' => $col->null && strtolower($col->null) === 'no' ? 'NO' : 'YES',
                'default' => $col->default === '' ? null : (string)$col->default,
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

        // remove the file
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

        // compare table columns and constraints against xml definition
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
            self::save($table_name);
            Tools::log()->critical('error-updating-table', [
                '%tableName%' => $table_name,
                'sql' => $sql
            ]);
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
                $sql .= self::needRename($db_cols, $xml_col) ?
                    self::sqlTool()->sqlRenameColumn($table_name, $xml_col['rename'], $xml_col['name']) :
                    self::sqlTool()->sqlAlterAddColumn($table_name, $xml_col);
                continue;
            }

            if (false === self::compareDataTypes($column['type'], $xml_col['type'])) {
                $sql .= self::sqlTool()->sqlAlterModifyColumn($table_name, $xml_col);
            }

            if ($column['default'] === null && $xml_col['default'] !== '') {
                $sql .= self::sqlTool()->sqlAlterColumnDefault($table_name, $xml_col);
            }

            if ($column['is_nullable'] !== $xml_col['null']) {
                $sql .= self::sqlTool()->sqlAlterColumnNull($table_name, $xml_col);
            }
        }

        return $sql;
    }

    private static function compareConstraints(string $table_name, array $xml_cons, array $db_cons): string
    {
        if (empty($xml_cons) || false === Tools::config('db_foreign_keys')) {
            return '';
        }

        // if you have to delete a constraint, it is better to delete them all
        $delete_cons = false;
        $sql_delete = '';
        $sql_delete_fk = '';

        foreach ($db_cons as $db_con) {
            if ($db_con['type'] === 'PRIMARY KEY') {
                // exclude primary key
                continue;
            } elseif ($db_con['type'] === 'FOREIGN KEY') {
                // it is better to delete the foreign keys before the rest
                $sql_delete_fk .= self::sqlTool()->sqlDropConstraint($table_name, $db_con);
            } else {
                $sql_delete .= self::sqlTool()->sqlDropConstraint($table_name, $db_con);
            }

            $column = self::searchInArray($xml_cons, 'name', $db_con['name']);
            if (empty($column)) {
                $delete_cons = true;
            }
        }

        // add new constraints
        $sql = '';
        foreach ($xml_cons as $xml_con) {
            // exclude primary keys because they have no name
            if (0 === strpos($xml_con['constraint'], 'PRIMARY')) {
                continue;
            }

            $column = self::searchInArray($db_cons, 'name', $xml_con['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAddConstraint($table_name, $xml_con['name'], $xml_con['constraint']);
            }
        }

        return $delete_cons ?
            $sql_delete_fk . $sql_delete . $sql :
            $sql;
    }

    private static function compareIndexes(string $table_name, array $xml_indexes, array $db_indexes): string
    {
        // Agregamos fs_ al inicio del 'name'
        // Así la comparación es correcta al buscar los indices
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
            return $sql;
        }

        // remove new indexes
        foreach ($db_indexes as $db_idx) {
            // delete if not found
            $column = self::searchInArray($xml_indexes, 'name', $db_idx['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlDropIndex($table_name, $db_idx);
            }
        }

        // add new indexes
        foreach ($xml_indexes as $xml_idx) {
            // add if not found
            $column = self::searchInArray($db_indexes, 'name', $xml_idx['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAddIndex($table_name, $xml_idx['name'], $xml_idx['columns']);
            }
        }

        return $sql;
    }

    private static function compareDataTypes(string $db_type, string $xml_type): bool
    {
        return self::db()->getEngine()->compareDataTypes($db_type, $xml_type);
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

    private static function sqlTool(): DataBaseQueries
    {
        if (null === self::$sql_tool) {
            self::$sql_tool = self::db()->getEngine()->getSQL();
        }

        return self::$sql_tool;
    }
}
