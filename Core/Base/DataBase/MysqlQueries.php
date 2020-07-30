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
class MysqlQueries implements DataBaseQueries
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
        return 'CAST(' . $colName . ' as UNSIGNED)';
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
        return 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $constraintName
            . ' ' . $this->fixPostgresql($sql) . ';';
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
        return 'ALTER TABLE ' . $tableName . ' ADD `' . $colData['name'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';
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
        return $colData['type'] === 'serial' ? '' : $this->sqlAlterModifyColumn($tableName, $colData);
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
        return $this->sqlAlterModifyColumn($tableName, $colData);
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
        $sql = 'ALTER TABLE ' . $tableName . ' MODIFY `' . $colData['name'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';

        return $this->fixPostgresql($sql);
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
        return 'SHOW COLUMNS FROM `' . $tableName . '`;';
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
        return 'SELECT CONSTRAINT_NAME as name, CONSTRAINT_TYPE as type'
            . ' FROM information_schema.table_constraints '
            . ' WHERE table_schema = schema()'
            . " AND table_name = '" . $tableName . "';";
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
        return 'SELECT t1.constraint_name as name,'
            . ' t1.constraint_type as type,'
            . ' t2.column_name as column_name,'
            . ' t2.referenced_table_name AS foreign_table_name,'
            . ' t2.referenced_column_name AS foreign_column_name,'
            . ' t3.update_rule AS on_update,'
            . ' t3.delete_rule AS on_delete'
            . ' FROM information_schema.table_constraints t1'
            . ' LEFT JOIN information_schema.key_column_usage t2'
            . ' ON t1.table_schema = t2.table_schema'
            . ' AND t1.table_name = t2.table_name'
            . ' AND t1.constraint_name = t2.constraint_name'
            . ' LEFT JOIN information_schema.referential_constraints t3'
            . ' ON t3.constraint_schema = t1.table_schema'
            . ' AND t3.constraint_name = t1.constraint_name'
            . ' WHERE t1.table_schema = SCHEMA()'
            . " AND t1.table_name = '" . $tableName . "'"
            . ' ORDER BY type DESC, name ASC;';
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
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', `' . $col['name'] . '` ' . $this->getTypeAndConstraints($col);
        }

        $sql = $this->fixPostgresql(substr($fields, 2));

        $charset = defined('FS_MYSQL_CHARSET') ? \FS_MYSQL_CHARSET : 'utf8';
        $collate = defined('FS_MYSQL_COLLATE') ? \FS_MYSQL_COLLATE : 'utf8_bin';
        return 'CREATE TABLE ' . $tableName . ' (' . $sql
            . $this->sqlTableConstraints($constraints) . ') '
            . 'ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' COLLATE=' . $collate . ';';
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
        $start = 'ALTER TABLE ' . $tableName . ' DROP';
        switch ($colData['type']) {
            case 'FOREIGN KEY':
                return $start . ' FOREIGN KEY ' . $colData['name'] . ';';

            case 'UNIQUE':
                return $start . ' INDEX ' . $colData['name'] . ';';

            default:
                return '';
        }
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
        return 'SHOW INDEXES FROM ' . $tableName . ';';
    }

    /**
     * Returns the SQL to get last ID assigned when performing an INSERT in the database
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return 'SELECT LAST_INSERT_ID() as num;';
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
            $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
        }

        return $this->fixPostgresql($sql);
    }

    /**
     * Removes PostgreSQL's problematic code
     *
     * @param string $sql
     *
     * @return string
     */
    private function fixPostgresql($sql)
    {
        $search = ['::character varying', 'without time zone', 'now()', 'CURRENT_TIMESTAMP', 'CURRENT_DATE'];
        $replace = ['', '', "'00:00'", "'" . date('Y-m-d') . " 00:00:00'", date("'Y-m-d'")];
        return str_replace($search, $replace, $sql);
    }

    /**
     * Returns a string with the columns constraints
     *
     * @param array $colData
     *
     * @return string
     */
    private function getConstraints($colData)
    {
        $notNull = ($colData['null'] === 'NO');
        $result = ' NULL';
        if ($notNull) {
            $result = ' NOT' . $result;
        }

        $defaultNull = ($colData['default'] === null);
        if ($defaultNull && !$notNull) {
            $result .= ' DEFAULT NULL';
        } elseif ($colData['default'] !== '') {
            $result .= ' DEFAULT ' . $colData['default'];
        }

        return $result;
    }

    /**
     * Generates the SQL with the field type and the DEFAULT and null constraints
     *
     * @param array $colData
     *
     * @return string
     */
    private function getTypeAndConstraints($colData)
    {
        switch ($colData['type']) {
            case 'serial':
                return ' INTEGER NOT NULL AUTO_INCREMENT';

            default:
                return ' ' . $colData['type'] . $this->getConstraints($colData);
        }
    }
}
