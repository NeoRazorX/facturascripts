<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\DataBase;

/**
 * Clase que recopila las sentencias SQL necesarias
 * por el motor de base de datos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class MysqlSQL implements DataBaseSQL
{
    /**
     * Genera el SQL con el tipo de campo y las constraints DEFAULT y null
     *
     * @param array $colData
     *
     * @return string
     */
    private function getTypeAndConstraints($colData)
    {
        $type = stripos('integer,serial', $colData['type']) === false ? strtolower($colData['type']) : FS_DB_INTEGER;
        switch (true) {
            case $type == 'serial':
            case stripos($colData['default'], 'nextval(') !== false:
                $contraints = ' NOT NULL AUTO_INCREMENT';
                break;

            default:
                $contraints = $this->getConstraints($colData);
                break;
        }

        return ' ' . $type . $contraints;
    }

    /**
     * Devuelve una string con las restricciones dels columnas.
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
        } else {
            if ($colData['default'] !== '') {
                $result .= ' DEFAULT ' . $colData['default'];
            }
        }

        return $result;
    }

    /**
     * Elimina código problemático de postgresql.
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
     * Devuelve el SQL necesario para convertir la columna a entero.
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
     * Devuleve el SQL para averiguar
     * el último ID asignado al hacer un INSERT
     * en la base de datos.
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return 'SELECT LAST_INSERT_ID() as num;';
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de las columnas de una tabla.
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
     * Devuelve el SQL para averiguar
     * la lista de restricciones de una tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints($tableName)
    {
        $sql = 'SELECT CONSTRAINT_NAME as name, CONSTRAINT_TYPE as type'
            . ' FROM information_schema.table_constraints '
            . ' WHERE table_schema = schema()'
            . " AND table_name = '" . $tableName . "';";

        return $sql;
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de restricciones avanzadas de una tabla.
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended($tableName)
    {
        $sql = 'SELECT t1.constraint_name as name,'
            . ' t1.constraint_type as type,'
            . ' t2.column_name,'
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

        return $sql;
    }

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
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
     * Devuelve el SQL para averiguar
     * la lista de indices de una tabla.
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
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
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

        return 'CREATE TABLE ' . $tableName . ' (' . $sql
            . $this->sqlTableConstraints($constraints) . ') '
            . 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;';
    }

    /**
     * Sentencia SQL para añadir una columna a una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName . ' ADD `' . $colData['name'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';

        return $sql;
    }

    /**
     * Sentencia SQL para modificar una columna de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName
            . ' MODIFY `' . $colData['name'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';

        return $this->fixPostgresql($sql);
    }

    /**
     * Sentencia SQL para modificar una constraint de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterConstraintDefault($tableName, $colData)
    {
        $result = '';
        if ($colData['type'] != 'serial') {
            $result = $this->sqlAlterModifyColumn($tableName, $colData);
        }

        return $result;
    }

    /**
     * Sentencia SQL para modificar una constraint null de un campo de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterConstraintNull($tableName, $colData)
    {
        return $this->sqlAlterModifyColumn($tableName, $colData);
    }

    /**
     * Sentencia SQL para eliminar una constraint de una tabla
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
                $sql = $start . ' FOREIGN KEY ' . $colData['name'] . ';';
                break;

            case 'UNIQUE':
                $sql = $start . ' INDEX ' . $colData['name'] . ';';
                break;

            default:
                $sql = '';
        }

        return $sql;
    }

    /**
     * Sentencia SQL para añadir una constraint a una tabla
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     *
     * @return string
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql)
    {
        return 'ALTER TABLE ' . $tableName
            . ' ADD CONSTRAINT ' . $constraintName . ' '
            . $this->fixPostgresql($sql) . ';';
    }

    /**
     * Sentencia SQL para comprobar una secuencia
     *
     * @param string $seqName
     *
     * @return string
     */
    public function sqlSequenceExists($seqName)
    {
        return $seqName;
    }
}
