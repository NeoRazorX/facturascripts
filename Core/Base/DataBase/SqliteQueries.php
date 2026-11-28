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
 * Sentencias SQL que necesita FacturaScripts para crear y actualizar las tablas en SQLite.
 *
 * SQLite tiene tipos dinámicos y un ALTER TABLE limitado: no permite cambiar el tipo, el valor
 * por defecto ni el NOT NULL de una columna, ni añadir o quitar claves ajenas en una tabla ya
 * creada. Esas sentencias devuelven una cadena vacía, así que esos cambios solo se aplican al
 * crear la tabla. Las restricciones UNIQUE que se añaden después se crean como índices únicos.
 * Las columnas serial se crean como INTEGER PRIMARY KEY AUTOINCREMENT.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class SqliteQueries implements DataBaseQueries
{
    /**
     * Devuelve el SQL para convertir una columna a entero.
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2Int(string $colName): string
    {
        return 'CAST(' . $colName . ' AS INTEGER)';
    }

    /**
     * Devuelve el SQL para añadir una restricción a una tabla ya creada. SQLite solo permite
     * hacerlo con las restricciones UNIQUE, que se crean como índices únicos.
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     *
     * @return string
     */
    public function sqlAddConstraint(string $tableName, string $constraintName, string $sql): string
    {
        if (1 !== preg_match('/^\s*UNIQUE\s*\((.+)\)\s*$/i', $sql, $matches)) {
            return '';
        }

        return 'CREATE UNIQUE INDEX IF NOT EXISTS ' . $constraintName . ' ON ' . $tableName . ' (' . $matches[1] . ');';
    }

    /**
     * Devuelve el SQL para crear un índice.
     *
     * @param string $tableName
     * @param string $indexName
     * @param string $columns
     *
     * @return string
     */
    public function sqlAddIndex(string $tableName, string $indexName, string $columns): string
    {
        return 'CREATE INDEX IF NOT EXISTS ' . $indexName . ' ON ' . $tableName . ' (' . $columns . ');';
    }

    /**
     * Devuelve el SQL para añadir una columna. SQLite no permite añadir una columna NOT NULL
     * sin valor por defecto, así que en ese caso se añade admitiendo nulos.
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn(string $tableName, array $colData): string
    {
        $sql = 'ALTER TABLE ' . $tableName . ' ADD COLUMN ' . $colData['name'] . ' ' . $this->sqlColumnType($colData['type']);

        $hasDefault = $colData['default'] !== null && $colData['default'] !== '';
        if ($hasDefault) {
            $sql .= ' DEFAULT ' . $colData['default'];
        }

        if ($colData['null'] === 'NO' && $hasDefault) {
            $sql .= ' NOT NULL';
        }

        return $sql . ';';
    }

    /**
     * SQLite no permite cambiar el valor por defecto de una columna.
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnDefault(string $tableName, array $colData): string
    {
        return '';
    }

    /**
     * SQLite no permite cambiar el NOT NULL de una columna.
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnNull(string $tableName, array $colData): string
    {
        return '';
    }

    /**
     * SQLite no permite cambiar el tipo de una columna (ni lo necesita, sus tipos son dinámicos).
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn(string $tableName, array $colData): string
    {
        return '';
    }

    /**
     * Devuelve el tipo de columna de SQLite para el tipo del XML. Los autoincrementales
     * (serial) pasan a INTEGER, que con la clave primaria es un alias del rowid.
     *
     * @param string $type
     *
     * @return string
     */
    public function sqlColumnType(string $type): string
    {
        return in_array(strtolower($type), ['serial', 'bigserial'], true) ? 'INTEGER' : $type;
    }

    /**
     * Devuelve el SQL para obtener las columnas de una tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlColumns(string $tableName): string
    {
        return 'SELECT name, type, "notnull", dflt_value, pk'
            . " FROM pragma_table_info('" . $this->escape($tableName) . "') ORDER BY 1 ASC;";
    }

    /**
     * Devuelve el SQL para obtener las restricciones UNIQUE añadidas como índices únicos.
     * SQLite no guarda el nombre de las claves ajenas ni de las restricciones de la tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints(string $tableName): string
    {
        return "SELECT 'UNIQUE' AS type, name FROM pragma_index_list('" . $this->escape($tableName) . "')"
            . " WHERE origin = 'c' AND \"unique\" = 1 ORDER BY 2 ASC;";
    }

    /**
     * Devuelve el SQL para obtener las restricciones UNIQUE con sus columnas.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended(string $tableName): string
    {
        return "SELECT 'UNIQUE' AS type, il.name, ii.name AS column_name,"
            . ' NULL AS foreign_table_name, NULL AS foreign_column_name, NULL AS on_update, NULL AS on_delete'
            . " FROM pragma_index_list('" . $this->escape($tableName) . "') il"
            . ' JOIN pragma_index_info(il.name) ii'
            . " WHERE il.origin = 'c' AND il.\"unique\" = 1 ORDER BY 2 ASC;";
    }

    /**
     * Devuelve el SQL para crear una tabla con sus columnas, restricciones e índices.
     *
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     * @param array $indexes
     *
     * @return string
     */
    public function sqlCreateTable(string $tableName, array $columns, array $constraints, array $indexes): string
    {
        $fields = '';
        foreach ($columns as $col) {
            // las columnas autoincrementales son la clave primaria con AUTOINCREMENT,
            // para que SQLite no reutilice los ids borrados (como MySQL y PostgreSQL)
            if (in_array(strtolower($col['type']), ['serial', 'bigserial'], true)) {
                $fields .= ', "' . $col['name'] . '" INTEGER PRIMARY KEY AUTOINCREMENT';
                $constraints = $this->removePrimaryKey($constraints, $col['name']);
                continue;
            }

            $fields .= ', "' . $col['name'] . '" ' . $this->sqlColumnType($col['type']);

            if (isset($col['null']) && $col['null'] === 'NO') {
                $fields .= ' NOT NULL';
            }

            if ($col['default'] !== '' && $col['default'] !== null) {
                $fields .= ' DEFAULT ' . $col['default'];
            }
        }

        $sql = 'CREATE TABLE ' . $tableName . ' (' . substr($fields, 2) . $this->sqlTableConstraints($constraints) . ');';
        foreach ($indexes as $idx) {
            $sql .= ' CREATE INDEX fs_' . $idx['name'] . ' ON ' . $tableName . ' (' . $idx['columns'] . ');';
        }

        return $sql;
    }

    /**
     * Devuelve el SQL para eliminar una restricción. Solo se pueden eliminar las UNIQUE
     * añadidas como índices únicos.
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlDropConstraint(string $tableName, array $colData): string
    {
        if (($colData['type'] ?? '') !== 'UNIQUE') {
            return '';
        }

        return 'DROP INDEX IF EXISTS ' . $colData['name'] . ';';
    }

    /**
     * Devuelve el SQL para eliminar un índice.
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlDropIndex(string $tableName, array $colData): string
    {
        return 'DROP INDEX IF EXISTS ' . $colData['name'] . ';';
    }

    /**
     * Devuelve el SQL para eliminar una tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlDropTable(string $tableName): string
    {
        return 'DROP TABLE IF EXISTS ' . $tableName . ';';
    }

    /**
     * Devuelve el SQL para obtener los índices creados con CREATE INDEX en una tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlIndexes(string $tableName): string
    {
        return 'SELECT il.name AS key_name, ii.name AS column_name'
            . " FROM pragma_index_list('" . $this->escape($tableName) . "') il"
            . ' JOIN pragma_index_info(il.name) ii'
            . " WHERE il.origin = 'c' ORDER BY 1 ASC, 2 ASC;";
    }

    /**
     * Devuelve el SQL para obtener el último id asignado por un INSERT.
     *
     * @return string
     */
    public function sqlLastValue(): string
    {
        return 'SELECT last_insert_rowid() AS num;';
    }

    /**
     * Devuelve el SQL para renombrar una columna (SQLite 3.25 o superior).
     *
     * @param string $tableName
     * @param string $old_column
     * @param string $new_column
     *
     * @return string
     */
    public function sqlRenameColumn(string $tableName, string $old_column, string $new_column): string
    {
        return 'ALTER TABLE ' . $tableName . ' RENAME COLUMN ' . $old_column . ' TO ' . $new_column . ';';
    }

    /**
     * Devuelve el SQL de las restricciones de la tabla para el CREATE TABLE.
     * Las claves ajenas solo se añaden si están activadas en la configuración.
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function sqlTableConstraints(array $xmlCons): string
    {
        $sql = '';
        foreach ($xmlCons as $res) {
            $isForeignKey = 0 === stripos(trim($res['constraint']), 'FOREIGN KEY');
            if ($isForeignKey && false === (bool)Tools::config('db_foreign_keys', true)) {
                continue;
            }

            $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
        }

        return $sql;
    }

    /**
     * Quita de la lista la restricción PRIMARY KEY de la columna indicada, que ya
     * se define en la propia columna.
     *
     * @param array $constraints
     * @param string $column
     *
     * @return array
     */
    private function removePrimaryKey(array $constraints, string $column): array
    {
        foreach ($constraints as $key => $constraint) {
            $pattern = '/^\s*PRIMARY\s+KEY\s*\(\s*"?' . preg_quote($column, '/') . '"?\s*\)\s*$/i';
            if (1 === preg_match($pattern, $constraint['constraint'])) {
                unset($constraints[$key]);
            }
        }

        return $constraints;
    }

    /**
     * Escapa las comillas simples de un texto para incluirlo en una consulta.
     *
     * @param string $value
     *
     * @return string
     */
    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
