<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    private static $checkedTables;

    /** @var DataBase */
    private static $dataBase;

    /** @var DataBaseQueries */
    private static $sqlTool;

    public static function createTable(string $tableName, array $structure = [], string $sqlAfter = ''): bool
    {
        if (self::isTableChecked($tableName)) {
            return false;
        }

        if (self::db()->tableExists($tableName)) {
            return false;
        }

        if (empty($structure)) {
            $filePath = self::getTableXmlLocation($tableName);
            $structure = self::readTableXml($filePath);
        }

        $sql = self::sqlTool()->sqlCreateTable($tableName, $structure['columns'], $structure['constraints']) . $sqlAfter;
        if (self::db()->exec($sql)) {
            self::save($tableName);

            Tools::log()->debug('table-checked', ['%tableName%' => $tableName]);
            return true;
        }

        self::save($tableName);
        return false;
    }

    public static function dropTable(string $tableName): bool
    {
        if (false === self::db()->tableExists($tableName)) {
            return false;
        }

        $sql = self::sqlTool()->sqlDropTable($tableName);
        if (self::db()->exec($sql)) {
            self::rebuild();

            Tools::log()->debug('table-deleted', ['%tableName%' => $tableName]);
            return true;
        }

        self::rebuild();
        return false;
    }

    public static function getTableXmlLocation(string $tableName): string
    {
        $fileName = Tools::folder('Dinamic', 'Table', $tableName . '.xml');
        if (Tools::config('debug') && false === file_exists($fileName)) {
            return Tools::folder('Core', 'Table', $tableName . '.xml');
        }

        return $fileName;
    }

    public static function isTableChecked(string $tableName): bool
    {
        if (null === self::$checkedTables) {
            // read the file
            $file = Tools::folder('MyFiles', self::FILE_NAME);
            if (false === file_exists($file)) {
                self::$checkedTables = [];
                return false;
            }

            $fileData = file_get_contents(Tools::folder('MyFiles', self::FILE_NAME));
            self::$checkedTables = json_decode($fileData, true) ?? [];
        }

        return in_array($tableName, self::$checkedTables);
    }

    public static function readTableXml(string $filePath): array
    {
        $structure = [
            'columns' => [],
            'constraints' => []
        ];

        if (false === file_exists($filePath)) {
            Tools::log()->critical('file-not-found', ['%fileName%' => $filePath]);
            return $structure;
        }

        $xml = simplexml_load_string(file_get_contents($filePath, true));
        if (false === $xml) {
            Tools::log()->critical('error-reading-file', ['%fileName%' => $filePath]);
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
                'default' => $col->default === '' ? null : (string)$col->default
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

        return $structure;
    }

    public static function rebuild(): void
    {
        self::$checkedTables = [];

        // remove the file
        $file = Tools::folder('MyFiles', self::FILE_NAME);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function updateTable(string $tableName, array $structure = []): bool
    {
        if (self::isTableChecked($tableName)) {
            return false;
        }

        if (empty($structure)) {
            $filePath = self::getTableXmlLocation($tableName);
            $structure = self::readTableXml($filePath);
        }

        // compare table columns and constraints against xml definition
        $dbCols = self::db()->getColumns($tableName);
        $dbCons = self::db()->getConstraints($tableName);
        $sql = self::compareColumns($tableName, $structure['columns'], $dbCols) .
            self::compareConstraints($tableName, $structure['constraints'], $dbCons);
        if (!empty($sql) && self::db()->exec($sql)) {
            self::save($tableName);

            Tools::log()->debug('table-checked', ['%tableName%' => $tableName]);
            return true;
        }

        self::save($tableName);

        Tools::log()->debug('table-checked', ['%tableName%' => $tableName]);
        return false;
    }

    private static function db(): DataBase
    {
        if (null === self::$dataBase) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    private static function compareColumns(string $tableName, array $xmlCols, array $dbCols): string
    {
        $sql = '';

        foreach ($xmlCols as $xmlCol) {
            $column = self::searchInArray($dbCols, 'name', $xmlCol['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAlterAddColumn($tableName, $xmlCol);
                continue;
            }

            if (false === self::compareDataTypes($column['type'], $xmlCol['type'])) {
                $sql .= self::sqlTool()->sqlAlterModifyColumn($tableName, $xmlCol);
            }

            if ($column['default'] === null && $xmlCol['default'] !== '') {
                $sql .= self::sqlTool()->sqlAlterColumnDefault($tableName, $xmlCol);
            }

            if ($column['is_nullable'] !== $xmlCol['null']) {
                $sql .= self::sqlTool()->sqlAlterColumnNull($tableName, $xmlCol);
            }
        }

        return $sql;
    }

    private static function compareConstraints(string $tableName, array $xmlCons, array $dbCons): string
    {
        if (empty($xmlCons) || false === Tools::config('db_foreign_keys')) {
            return '';
        }

        // if you have to delete a constraint, it is better to delete them all
        $deleteCons = false;
        $sqlDelete = '';
        $sqlDeleteFK = '';

        foreach ($dbCons as $dbCon) {
            if ($dbCon['type'] === 'PRIMARY KEY') {
                // exclude primary key
                continue;
            } elseif ($dbCon['type'] === 'FOREIGN KEY') {
                // it is better to delete the foreign keys before the rest
                $sqlDeleteFK .= self::sqlTool()->sqlDropConstraint($tableName, $dbCon);
            } else {
                $sqlDelete .= self::sqlTool()->sqlDropConstraint($tableName, $dbCon);
            }

            $column = self::searchInArray($xmlCons, 'name', $dbCon['name']);
            if (empty($column)) {
                $deleteCons = true;
            }
        }

        // add new constraints
        $sql = '';
        foreach ($xmlCons as $xmlCon) {
            // exclude primary keys because they have no name
            if (0 === strpos($xmlCon['constraint'], 'PRIMARY')) {
                continue;
            }

            $column = self::searchInArray($dbCons, 'name', $xmlCon['name']);
            if (empty($column)) {
                $sql .= self::sqlTool()->sqlAddConstraint($tableName, $xmlCon['name'], $xmlCon['constraint']);
            }
        }

        return $deleteCons ?
            $sqlDeleteFK . $sqlDelete . $sql :
            $sql;
    }

    private static function compareDataTypes(string $dbType, string $xmlType): bool
    {
        return self::db()->getEngine()->compareDataTypes($dbType, $xmlType);
    }

    private static function save(string $tableName): void
    {
        self::$checkedTables[] = $tableName;

        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));

        file_put_contents(
            Tools::folder('MyFiles', self::FILE_NAME),
            json_encode(self::$checkedTables, JSON_PRETTY_PRINT)
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
        if (null === self::$sqlTool) {
            self::$sqlTool = self::db()->getEngine()->getSQL();
        }

        return self::$sqlTool;
    }
}
