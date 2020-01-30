<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Class that gathers all the needed SQL sentences by the database engine
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
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
    public function sql2Int($colName)
    {
        return 'CAST(' . $colName . ' as INTEGER)';
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
    public function sqlAddConstraint($tableName, $constraintName, $sql)
    {
        return 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $constraintName . ' ' . $sql . ';';
    }

    /**
     * Returns the SQL needed to add a column to a table
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn($tableName, $colData)
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
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterColumnDefault($tableName, $colData)
    {
        if ($colData['type'] === 'serial') {
            return '';
        }

        $action = empty($colData['default']) ? ' DROP DEFAULT' : ' SET DEFAULT ' . $colData['default'];
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . ';';
    }

    /**
     * SQL statement to alter a null constraint in a table column
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterColumnNull($tableName, $colData)
    {
        $action = $colData['null'] === 'YES' ? ' DROP ' : ' SET ';
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . 'NOT NULL;';
    }

    /**
     * Returns the SQL needed to alter a column in a table
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData)
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
    public function sqlColumns($tableName)
    {
        return 'SELECT column_name as name, data_type as type,'
            . 'character_maximum_length, column_default as default,'
            . 'is_nullable'
            . ' FROM information_schema.columns'
            . " WHERE table_catalog = '" . \FS_DB_NAME . "'"
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
    public function sqlConstraints($tableName)
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
    public function sqlConstraintsExtended($tableName)
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
     * @param array  $columns
     * @param array  $constraints
     *
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $constraints)
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
            . $this->sqlTableConstraints($constraints) . ');';
    }

    /**
     * Returns the SQL needed to remove a constraint from a table
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlDropConstraint($tableName, $colData)
    {
        return 'ALTER TABLE ' . $tableName . ' DROP CONSTRAINT ' . $colData['name'] . ';';
    }

    /**
     * SQL statement to drop a given table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlDropTable($tableName)
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
    public function sqlIndexes($tableName)
    {
        return "SELECT indexname as Key_name FROM pg_indexes WHERE tablename = '" . $tableName . "';";
    }

    /**
     * Returns the SQL to get last ID assigned when performing an INSERT in the database
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return 'SELECT lastval() as num;';
    }

    /**
     * Generates the needed SQL to establish the given constraints
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function sqlTableConstraints($xmlCons)
    {
        $sql = '';

        foreach ($xmlCons as $res) {
            $value = strtolower($res['constraint']);
            if (false !== strpos($value, 'primary key')) {
                $sql .= ', ' . $res['constraint'];
                continue;
            }

            if (\FS_DB_FOREIGN_KEYS || 0 !== strpos($res['constraint'], 'FOREIGN KEY')) {
                $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
            }
        }

        return $sql;
    }
}
