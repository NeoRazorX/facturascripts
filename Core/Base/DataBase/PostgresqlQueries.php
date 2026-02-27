<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Class that gathers all the needed SQL sentences by the database engine
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class PostgresqlQueries implements DataBaseQueries
{
    /**
     * Returns the needed SQL to convert a column to integer
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2Int(string $colName): string
    {
        return 'CAST(' . $colName . ' as BIGINT)';
    }

    /**
     * Returns the SQL needed to add a constraint to a table
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     *
     * @return string
     */
    public function sqlAddConstraint(string $tableName, string $constraintName, string $sql): string
    {
        // reemplazamos (user) por ("user") para evitar problemas
        $sql = str_replace('(user)', '("user")', $sql);

        return 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $constraintName . ' ' . $sql . ';';
    }

    /**
     * Returns the SQL needed to add a column to a table
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn(string $tableName, array $colData): string
    {
        $sql = 'ALTER TABLE ' . $tableName . ' ADD COLUMN ' . $colData['name'] . ' ' . $colData['type'];

        if ($colData['type'] === 'serial') {
            $sql .= " DEFAULT nextval('" . $tableName . "_" . $colData['name'] . "_seq'::regclass)";
        } elseif ($colData['default'] !== '') {
            $sql .= ' DEFAULT ' . $colData['default'];
        }

        if ($colData['null'] === 'NO') {
            $sql .= ' NOT NULL';
        }

        return $sql . ';';
    }

    /**
     * Returns the needed SQL to alter a column default constraint
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnDefault(string $tableName, array $colData): string
    {
        if ($colData['type'] === 'serial') {
            return '';
        }

        $action = empty($colData['default']) ? ' DROP DEFAULT' : ' SET DEFAULT ' . $colData['default'];
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . ';';
    }

    /**
     * Returns the SQL needed to add an index to a table
     *
     * @param string $tableName
     * @param string $indexName
     * @param string $columns
     *
     * @return string
     */
    public function sqlAddIndex(string $tableName, string $indexName, string $columns): string
    {
        return 'CREATE INDEX ' . $indexName . ' ON ' . $tableName . ' (' . $columns . ');';
    }

    /**
     * SQL statement to alter a null constraint in a table column
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnNull(string $tableName, array $colData): string
    {
        if ($colData['type'] === 'serial') {
            return '';
        }

        $action = $colData['null'] === 'YES' ? ' DROP ' : ' SET ';
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . 'NOT NULL;';
    }

    /**
     * Returns the SQL needed to alter a column in a table
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn(string $tableName, array $colData): string
    {
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . ' TYPE ' . $colData['type'] . ';';
    }

    /**
     * Returns the SQL needed to get the list of columns in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlColumns(string $tableName): string
    {
        return 'SELECT column_name as name, data_type as type,'
            . 'character_maximum_length, column_default as default,'
            . 'is_nullable'
            . ' FROM information_schema.columns'
            . " WHERE table_catalog = '" . Tools::config('db_name') . "'"
            . " AND table_name = '" . $tableName . "'"
            . ' ORDER BY 1 ASC;';
    }

    /**
     * Returns the SQL needed to get the list of constraints in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints(string $tableName): string
    {
        return 'SELECT tc.constraint_type as type, tc.constraint_name as name'
            . ' FROM information_schema.table_constraints AS tc'
            . " WHERE tc.table_name = '" . $tableName . "'"
            . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
            . ' ORDER BY 1 DESC, 2 ASC;';
    }

    /**
     * Returns the SQL needed to get the list of advanced constraints in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended(string $tableName): string
    {
        return 'SELECT tc.constraint_type as type, tc.constraint_name as name,'
            . 'kcu.column_name,'
            . 'ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name,'
            . 'rc.update_rule AS on_update, rc.delete_rule AS on_delete'
            . ' FROM information_schema.table_constraints AS tc'
            . ' LEFT JOIN information_schema.key_column_usage AS kcu'
            . ' ON kcu.constraint_schema = tc.constraint_schema'
            . ' AND kcu.constraint_catalog = tc.constraint_catalog'
            . ' AND kcu.constraint_name = tc.constraint_name'
            . ' LEFT JOIN information_schema.constraint_column_usage AS ccu'
            . ' ON ccu.constraint_schema = tc.constraint_schema'
            . ' AND ccu.constraint_catalog = tc.constraint_catalog'
            . ' AND ccu.constraint_name = tc.constraint_name'
            . ' LEFT JOIN information_schema.referential_constraints rc'
            . ' ON rc.constraint_schema = tc.constraint_schema'
            . ' AND rc.constraint_catalog = tc.constraint_catalog'
            . ' AND rc.constraint_name = tc.constraint_name'
            . " WHERE tc.table_name = '" . $tableName . "'"
            . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
            . ' ORDER BY 1 DESC, 2 ASC;';
    }

    /**
     * Returns the SQL needed to create a table with the given structure
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
        $serials = ['serial', 'bigserial'];
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', "' . $col['name'] . '" ' . $col['type'];

            if (isset($col['null']) && $col['null'] === 'NO') {
                $fields .= ' NOT NULL';
            }

            if (in_array($col['type'], $serials, false)) {
                continue;
            }

            if ($col['default'] !== '') {
                $fields .= ' DEFAULT ' . (is_null($col['default']) ? 'NULL' : $col['default']);
            }
        }

        return 'CREATE TABLE ' . $tableName . ' (' . substr($fields, 2)
            . $this->sqlTableConstraints($constraints) . ');'
            . $this->sqlTableIndexes($tableName, $indexes);
    }

    /**
     * Returns the SQL needed to remove a constraint from a table
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlDropConstraint(string $tableName, array $colData): string
    {
        return 'ALTER TABLE ' . $tableName . ' DROP CONSTRAINT ' . $colData['name'] . ';';
    }

    public function sqlDropIndex(string $tableName, array $colData): string
    {
        return 'DROP INDEX IF EXISTS ' . $colData['name'] . ';';
    }

    /**
     * SQL statement to drop a given table
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
     * Returns the SQL needed to get the list of indexes in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlIndexes(string $tableName): string
    {
        return "SELECT
                  i.relname AS key_name,
                  a.attname AS column_name
                FROM
                  pg_class t
                JOIN
                  pg_index ix ON t.oid = ix.indrelid
                JOIN
                  pg_class i ON i.oid = ix.indexrelid
                JOIN
                  pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
                WHERE
                  t.relkind = 'r'
                  AND t.relname = '" . $tableName . "'
                ORDER BY
                  i.relname,
                  a.attname;";
    }

    /**
     * Returns the SQL to get last ID assigned when performing an INSERT in the database
     *
     * @return string
     */
    public function sqlLastValue(): string
    {
        return 'SELECT lastval() as num;';
    }

    public function sqlRenameColumn(string $tableName, string $old_column, string $new_column): string
    {
        return 'ALTER TABLE ' . $tableName . ' RENAME COLUMN ' . $old_column . ' TO ' . $new_column . ';';
    }

    /**
     * Generates the needed SQL to establish the given constraints
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function sqlTableConstraints(array $xmlCons): string
    {
        $sql = '';

        foreach ($xmlCons as $res) {
            $value = strtolower($res['constraint']);
            if (false !== strpos($value, 'primary key')) {
                $sql .= ', ' . $res['constraint'];
                continue;
            }

            if (Tools::config('db_foreign_keys') || 0 !== strpos($res['constraint'], 'FOREIGN KEY')) {
                // reemplazamos (user) por ("user") para evitar problemas
                $res['constraint'] = str_replace('(user)', '("user")', $res['constraint']);

                $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
            }
        }

        return $sql;
    }

    /**
     * Generates the needed SQL to create the indexes in a table
     *
     * @param string $tableName
     * @param array $xmlIndexes
     *
     * @return string
     */
    private function sqlTableIndexes(string $tableName, array $xmlIndexes): string
    {
        $sql = '';
        foreach ($xmlIndexes as $idx) {
            $sql .= ' CREATE INDEX fs_' . $idx['name'] . ' ON ' . $tableName . ' (' . $idx['columns'] . ');';
        }

        return $sql;
    }
}
