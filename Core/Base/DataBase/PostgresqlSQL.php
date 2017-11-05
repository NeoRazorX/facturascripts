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
class PostgresqlSQL implements DataBaseSQL
{
    /**
     * Devuelve el SQL necesario para convertir
     * la columna a entero.
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
     * Devuleve el SQL para averiguar
     * el último ID asignado al hacer un INSERT
     * en la base de datos.
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return 'SELECT lastval() as num;';
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
        $sql = 'SELECT column_name as name, data_type as type,'
            . 'character_maximum_length, column_default as default,'
            . 'is_nullable'
            . ' FROM information_schema.columns'
            . " WHERE table_catalog = '" . FS_DB_NAME . "'"
            . " AND table_name = '" . $tableName . "'"
            . ' ORDER BY 1 ASC;';

        return $sql;
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
        $sql = 'SELECT tc.constraint_type as type, tc.constraint_name as name'
            . ' FROM information_schema.table_constraints AS tc'
            . " WHERE tc.table_name = '" . $tableName . "'"
            . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
            . ' ORDER BY 1 DESC, 2 ASC;';

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
        $sql = 'SELECT tc.constraint_type as type, tc.constraint_name as name,'
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
            . ' AND ccu.column_name = kcu.column_name'
            . ' LEFT JOIN information_schema.referential_constraints rc'
            . ' ON rc.constraint_schema = tc.constraint_schema'
            . ' AND rc.constraint_catalog = tc.constraint_catalog'
            . ' AND rc.constraint_name = tc.constraint_name'
            . " WHERE tc.table_name = '" . $tableName . "'"
            . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
            . ' ORDER BY 1 DESC, 2 ASC;';

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
            $value = strtolower($res['constraint']);
            if (false !== strpos($value, 'primary key')) {
                $sql .= ', ' . $res['constraint'];
                continue;
            }

            if (FS_FOREIGN_KEYS === '1' || 0 !== strpos($res['constraint'], 'FOREIGN KEY')) {
                $sql .= ', CONSTRAINT ' . $res['name'] . ' ' . $res['constraint'];
            }
        }

        return $sql;
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
        return "SELECT indexname as Key_name FROM pg_indexes WHERE tablename = '" . $tableName . "';";
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
        $serials = ['serial', 'bigserial'];
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', ' . $col['name'] . ' ' . $col['type'];

            if ($col['null'] === 'NO') {
                $fields .= ' NOT NULL';
            }

            if (in_array($col['type'], $serials, false)) {
                continue;
            }

            if ($col['default'] !== '') {
                $fields .= ' DEFAULT ' . $col['default'];
            }
        }

        $sql = 'CREATE TABLE ' . $tableName . ' (' . substr($fields, 2)
            . $this->sqlTableConstraints($constraints) . ');';

        return $sql;
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
        $sql = 'ALTER TABLE ' . $tableName
            . ' ADD COLUMN ' . $colData['name'] . ' ' . $colData['type'];

        if ($colData['default'] !== '') {
            $sql .= ' DEFAULT ' . $colData['default'];
        }

        if ($colData['null'] === 'NO') {
            $sql .= ' NOT NULL';
        }

        return $sql . ';';
    }

    /**
     * Sentencia SQL para modificar una columna a una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName
            . ' ALTER COLUMN ' . $colData['name'] . ' TYPE ' . $colData['type'];

        return $sql . ';';
    }

    /**
     * Sentencia SQL para modificar un valor por defecto de un campo de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterConstraintDefault($tableName, $colData)
    {
        $action = ($colData['default'] !== '') ? ' SET DEFAULT ' . $colData['default'] : ' DROP DEFAULT';

        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . ';';
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
        $action = ($colData['null'] === 'YES') ? ' DROP ' : ' SET ';

        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['name'] . $action . 'NOT NULL;';
    }

    /**
     * Sentencia SQL para eliminar una constraint a una tabla
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
        return 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $constraintName . ' ' . $sql . ';';
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
        return "SELECT '" . $seqName . "' FROM pg_class where relname = '" . $seqName . "';";
    }
}
