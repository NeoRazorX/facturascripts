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

use FacturaScripts\Core\Tools;

/**
 * Class that gathers all the needed SQL sentences by the database engine.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class SqliteQueries implements DataBaseQueries
{
    public function sql2Int(string $colName): string
    {
        return 'CAST(' . $colName . ' as INTEGER)';
    }

    public function sqlAddConstraint(string $tableName, string $constraintName, string $sql): string
    {
        return '';
    }

    public function sqlAddIndex(string $tableName, string $indexName, string $columns): string
    {
        return 'CREATE INDEX ' . $indexName . ' ON ' . $tableName . ' (' . $columns . ');';
    }

    public function sqlAlterAddColumn(string $tableName, array $colData): string
    {
        return 'ALTER TABLE ' . $tableName . ' ADD COLUMN ' . $colData['name'] . ' '
            . $this->getTypeAndConstraints($colData, false) . ';';
    }

    public function sqlAlterColumnDefault(string $tableName, array $colData): string
    {
        return '';
    }

    public function sqlAlterColumnNull(string $tableName, array $colData): string
    {
        return '';
    }

    public function sqlAlterModifyColumn(string $tableName, array $colData): string
    {
        return '';
    }

    public function sqlColumns(string $tableName): string
    {
        return 'PRAGMA table_info("' . $tableName . '");';
    }

    public function sqlConstraints(string $tableName): string
    {
        return "SELECT 'PRIMARY' AS name, 'PRIMARY KEY' AS type"
            . ' FROM pragma_table_info(' . $this->quote($tableName) . ')'
            . ' WHERE pk > 0 LIMIT 1;';
    }

    public function sqlConstraintsExtended(string $tableName): string
    {
        return "SELECT 'PRIMARY' AS name, 'PRIMARY KEY' AS type,"
            . ' name AS column_name,'
            . ' NULL AS foreign_table_name,'
            . ' NULL AS foreign_column_name,'
            . ' NULL AS on_update,'
            . ' NULL AS on_delete'
            . ' FROM pragma_table_info(' . $this->quote($tableName) . ')'
            . ' WHERE pk > 0;';
    }

    public function sqlCreateTable(string $tableName, array $columns, array $constraints, array $indexes): string
    {
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', "' . $col['name'] . '" ' . $this->getTypeAndConstraints($col);
        }

        return 'CREATE TABLE ' . $tableName . ' (' . substr($fields, 2)
            . $this->buildTableConstraints($constraints, $columns) . ');'
            . $this->sqlTableIndexes($tableName, $indexes);
    }

    public function sqlDropConstraint(string $tableName, array $colData): string
    {
        return '';
    }

    public function sqlDropIndex(string $tableName, array $colData): string
    {
        return 'DROP INDEX IF EXISTS ' . $colData['name'] . ';';
    }

    public function sqlDropTable(string $tableName): string
    {
        return 'DROP TABLE IF EXISTS ' . $tableName . ';';
    }

    public function sqlIndexes(string $tableName): string
    {
        return 'SELECT il.name AS key_name, ii.name AS column_name'
            . ' FROM pragma_index_list(' . $this->quote($tableName) . ') il'
            . ' JOIN pragma_index_info(il.name) ii'
            . " WHERE il.origin != 'pk'"
            . ' ORDER BY il.name ASC, ii.seqno ASC;';
    }

    public function sqlLastValue(): string
    {
        return 'SELECT last_insert_rowid() as num;';
    }

    public function sqlRenameColumn(string $tableName, string $old_column, string $new_column): string
    {
        return 'ALTER TABLE ' . $tableName . ' RENAME COLUMN ' . $old_column . ' TO ' . $new_column . ';';
    }

    public function sqlTableConstraints(array $xmlCons): string
    {
        return $this->buildTableConstraints($xmlCons, []);
    }

    private function getConstraints(array $colData, bool $includeDefault = true): string
    {
        $result = '';

        if (($colData['null'] ?? 'YES') === 'NO') {
            $result .= ' NOT NULL';
        }

        if (false === $includeDefault) {
            return $result;
        }

        if ($colData['default'] === null && ($colData['null'] ?? 'YES') !== 'NO') {
            return $result . ' DEFAULT NULL';
        }

        if ($colData['default'] !== '') {
            return $result . ' DEFAULT ' . $this->normalizeDefault($colData['default']);
        }

        return $result;
    }

    private function getTypeAndConstraints(array $colData, bool $includeDefault = true): string
    {
        switch ($colData['type']) {
            case 'serial':
                return 'INTEGER PRIMARY KEY AUTOINCREMENT';

            default:
                return $colData['type'] . $this->getConstraints($colData, $includeDefault);
        }
    }

    private function normalizeDefault($default): string
    {
        if ($default === null) {
            return 'NULL';
        }

        if (in_array($default, ['CURRENT_DATE', 'CURRENT_TIMESTAMP', 'NULL'], true)) {
            return (string)$default;
        }

        if (in_array($default, ['false', 'true'], true)) {
            return strtoupper((string)$default);
        }

        return (string)$default;
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function buildTableConstraints(array $xmlCons, array $columns): string
    {
        $hasSerialPrimary = false;
        foreach ($columns as $column) {
            if ($column['type'] === 'serial') {
                $hasSerialPrimary = true;
                break;
            }
        }

        $sql = '';
        foreach ($xmlCons as $res) {
            $value = strtolower($res['constraint']);

            if ($hasSerialPrimary && false !== strpos($value, 'primary key')) {
                continue;
            }

            if (
                false !== strpos($value, 'primary key') ||
                false !== strpos($value, 'unique') ||
                (false !== strpos($value, 'foreign key') && Tools::config('db_foreign_keys', true))
            ) {
                $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
            }
        }

        return $sql;
    }

    private function sqlTableIndexes(string $tableName, array $xmlIndexes): string
    {
        $sql = '';
        foreach ($xmlIndexes as $idx) {
            $sql .= ' CREATE INDEX fs_' . $idx['name'] . ' ON ' . $tableName . ' (' . $idx['columns'] . ');';
        }

        return $sql;
    }
}
