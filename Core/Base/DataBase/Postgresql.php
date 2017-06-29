<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Clase para conectar a PostgreSQL.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Postgresql implements DatabaseEngine {

    /**
     * Devuelve el motor de base de datos y la versión.
     * @param resource $link
     * @return string
     */
    public function version($link) {
        return 'POSTGRESQL ' . pg_version($link)['server'];
    }

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     * @param array $colData
     * @return array
     */
    public function columnFromData($colData) {
        $colData['extra'] = NULL;

        if ($colData['character_maximum_length'] != NULL) {
            $colData['type'] .= '(' . $colData['character_maximum_length'] . ')';
        }

        return $colData;
    }

    /**
     * Conecta a la base de datos.
     * @param string $error
     * @return boolean|null
     */
    public function connect(&$error) {
        if (!function_exists('pg_connect')) {
            $error = 'No tienes instalada la extensión de PHP para PostgreSQL.';
            return NULL;
        }

        $result = pg_connect('host=' . FS_DB_HOST . ' dbname=' . FS_DB_NAME . ' port=' . FS_DB_PORT . ' user=' . FS_DB_USER . ' password=' . FS_DB_PASS);
        if (!$result) {
            $error = pg_last_error();
            return NULL;
        }

        $this->exec($result, "SET DATESTYLE TO ISO, DMY;"); /// establecemos el formato de fecha para la conexión

        return $result;
    }

    /**
     * Desconecta de la base de datos.
     * @param resource $link
     * @return boolean
     */
    public function close($link) {
        return pg_close($link);
    }

    /**
     * Devuelve el error de la ultima sentencia ejecutada
     * @param resource $link
     * @return string
     */
    public function errorMessage($link) {
        return pg_last_error($link);
    }

    /**
     * Inicia una transacción SQL.
     * @param resource $link
     * @return boolean
     */
    public function beginTransaction($link) {
        return $this->exec($link, 'BEGIN TRANSACTION;');
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @param resource $link
     * @return boolean
     */
    public function commit($link) {
        return $this->exec($link, 'COMMIT;');
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @param resource $link
     * @return boolean
     */
    public function rollback($link) {
        return $this->exec($link, 'ROLLBACK;');
    }

    /**
     * Indica si la conexión está en transacción
     * @param resource $link
     */
    public function inTransaction($link) {
        $status = pg_transaction_status($link);
        switch ($status) {
            case PGSQL_TRANSACTION_ACTIVE:
            case PGSQL_TRANSACTION_INTRANS:
            case PGSQL_TRANSACTION_INERROR:
                $result = TRUE;
                break;

            default:
                $result = FALSE;
                break;
        }
        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     * @param resource $link
     * @param string $sql
     * @return resource|FALSE
     */
    public function select($link, $sql) {
        $result = FALSE;
        try {
            $aux = pg_query($link, $sql);
            if ($aux) {
                $result = pg_fetch_all($aux);
                pg_free_result($aux);
            }
        } catch (\Exception $e) {
            $result = FALSE;
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos 
     * (inserts, updates o deletes).
     * @param resource $link
     * @param string $sql
     * @return boolean
     */
    public function exec($link, $sql) {
        $result = FALSE;
        try {
            $aux = pg_query($link, $sql);
            if ($aux) {
                pg_free_result($aux);
                $result = TRUE;
            }
        } catch (\Exception $e) {
            $result = FALSE;
        }

        return $result;
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param resource $link
     * @param string $str
     * @return string
     */
    public function escapeString($link, $str) {
        return pg_escape_string($link, $str);
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle() {
        return 'd-m-Y';
    }

    /**
     * Devuelve el SQL necesario para convertir
     * la columna a entero.
     * @param string $colName
     * @return string
     */
    public function sql2int($colName) {
        return 'CAST(' . $colName . ' as INTEGER)';
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
     * @param string $dbType
     * @param string $xmlType
     * @return boolean
     */
    public function compareDataTypes($dbType, $xmlType) {
        return ($dbType == $xmlType);
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @param resource $link
     * @return string
     */
    public function listTables($link) {
        $tables = [];
        $sql = "SELECT tablename"
                . " FROM pg_catalog.pg_tables"
                . " WHERE schemaname NOT IN ('pg_catalog','information_schema')"
                . " ORDER BY tablename ASC;";

        $aux = $this->select($link, $sql);
        if ($aux) {
            foreach ($aux as $a) {
                $tables[] = $a['tablename'];
            }
        }

        return $tables;
    }

    /**
     * A partir del campo default de una tabla
     * comprueba si se refiere a una secuencia, y si es así
     * comprueba la existencia de la secuencia. Si no la encuentra
     * la crea.
     * @param resource $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    public function checkSequence($link, $tableName, $default, $colname) {
        $aux = explode("'", $default);
        if (count($aux) == 3) {
            $data = $this->select($link, $this->sqlSequenceExists($aux[1]));
            if (!$data) {             /// ¿Existe esa secuencia?
                $data = $this->select($link, "SELECT MAX(" . $colname . ")+1 as num FROM " . $tableName . ";");
                $this->exec($link, "CREATE SEQUENCE " . $aux[1] . " START " . $data[0]['num'] . ";");
            }
        }
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param string $tableName
     * @return boolean
     */
    public function checkTableAux($link, $tableName, &$error) {
        return TRUE;
    }

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     * @param array $xmlCons
     * @return string
     */
    public function generateTableConstraints($xmlCons) {
        $sql = '';

        foreach ($xmlCons as $res) {
            $value = strtolower($res['consulta']);
            if (strstr($value, 'primary key')) {
                $sql .= ', ' . $res['consulta'];
                continue;
            }

            if (FS_FOREIGN_KEYS || substr($res['consulta'], 0, 11) != 'FOREIGN KEY') {
                $sql .= ', CONSTRAINT ' . $res['nombre'] . ' ' . $res['consulta'];
            }
        }

        return $sql;
    }

    /**
     * Devuleve el SQL para averiguar
     * el último ID asignado al hacer un INSERT 
     * en la base de datos.
     * @return string
     */
    public function sqlLastValue() {
        return 'SELECT lastval() as num;';
    }

    /**
     * Devuelve el SQL para averiguar 
     * la lista de las columnas de una tabla.
     * @param string $tableName
     * @return string
     */
    public function sqlColumns($tableName) {
        $sql = "SELECT column_name as name, data_type as type,"
                . "character_maximum_length, column_default as default,"
                . "is_nullable"
                . " FROM information_schema.columns"
                . " WHERE table_catalog = '" . FS_DB_NAME . "'"
                . " AND table_name = '" . $tableName . "'"
                . " ORDER BY 1 ASC;";

        return $sql;
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de restricciones de una tabla.
     * @param string $tableName
     * @return string
     */
    public function sqlConstraints($tableName) {
        $sql = "SELECT tc.constraint_type as type, tc.constraint_name as name"
                . " FROM information_schema.table_constraints AS tc"
                . " WHERE tc.table_name = '" . $tableName . "'"
                . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
                . " ORDER BY 1 DESC, 2 ASC;";
        return $sql;
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de restricciones avanzadas de una tabla.
     * @param string $tableName
     * @return string
     */
    public function sqlConstraintsExtended($tableName) {
        $sql = "SELECT tc.constraint_type as type, tc.constraint_name as name,"
                . "kcu.column_name,"
                . "ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name,"
                . "rc.update_rule AS on_update, rc.delete_rule AS on_delete"
                . " FROM information_schema.table_constraints AS tc"
                . " LEFT JOIN information_schema.key_column_usage AS kcu"
                . " ON kcu.constraint_schema = tc.constraint_schema"
                . " AND kcu.constraint_catalog = tc.constraint_catalog"
                . " AND kcu.constraint_name = tc.constraint_name"
                . " LEFT JOIN information_schema.constraint_column_usage AS ccu"
                . " ON ccu.constraint_schema = tc.constraint_schema"
                . " AND ccu.constraint_catalog = tc.constraint_catalog"
                . " AND ccu.constraint_name = tc.constraint_name"
                . " AND ccu.column_name = kcu.column_name"
                . " LEFT JOIN information_schema.referential_constraints rc"
                . " ON rc.constraint_schema = tc.constraint_schema"
                . " AND rc.constraint_catalog = tc.constraint_catalog"
                . " AND rc.constraint_name = tc.constraint_name"
                . " WHERE tc.table_name = '" . $tableName . "'"
                . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
                . " ORDER BY 1 DESC, 2 ASC;";

        return $sql;
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de indices de una tabla.
     * @param string $tableName
     * @return string
     */
    public function sqlIndexes($tableName) {
        return "SELECT indexname as Key_name FROM pg_indexes WHERE tablename = '" . $tableName . "';";
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $columns
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $constraints) {
        $serials = ['serial', 'bigserial'];
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', ' . $col['nombre'] . ' ' . $col['tipo'];

            if ($col['nulo'] == 'NO') {
                $fields .= ' NOT NULL';
            }

            if (in_array($col['tipo'], $serials)) {
                continue;
            }

            if ($col['defecto'] !== NULL) {
                $fields .= ' DEFAULT ' . $col['defecto'];
            }
        }

        $sql = 'CREATE TABLE ' . $tableName . ' (' . substr($fields, 2)
                . $this->generateTableConstraints($constraints) . ');';
        return $sql;
    }

    /**
     * Sentencia SQL para añadir una columna a una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterAddColumn($tableName, $colData) {
        $sql = 'ALTER TABLE ' . $tableName
                . ' ADD COLUMN ' . $colData['nombre'] . " " . $colData['tipo'];

        if ($colData['defecto'] !== NULL) {
            $sql .= ' DEFAULT ' . $colData['defecto'];
        }

        if ($colData['nulo'] == 'NO') {
            $sql .= ' NOT NULL';
        }

        return $sql . ';';
    }

    /**
     * Sentencia SQL para modificar una columna a una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData) {
        $sql = 'ALTER TABLE ' . $tableName
                . ' ALTER COLUMN ' . $colData['nombre'] . ' TYPE ' . $colData['tipo'];
        return $sql . ";";
    }

    /**
     * Sentencia SQL para modificar un valor por defecto de un campo de una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterConstraintDefault($tableName, $colData) {
        $action = ($colData['defecto'] !== NULL) ? " SET DEFAULT " . $colData['defecto'] : " DROP DEFAULT";

        return "ALTER TABLE " . $tableName . " ALTER COLUMN " . $colData['nombre'] . $action;
    }

    /**
     * Sentencia SQL para modificar una constraint null de un campo de una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterConstraintNull($tableName, $colData) {
        $action = ($colData['nulo'] == 'YES') ? " DROP " : " SET ";
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['nombre'] . $action . "NOT NULL;";
    }

    /**
     * Sentencia SQL para eliminar una constraint a una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlDropConstraint($tableName, $colData) {
        return "ALTER TABLE " . $tableName . " DROP CONSTRAINT " . $colData['name'] . ";";
    }

    /**
     * Sentencia SQL para añadir una constraint a una tabla
     * @param string $tableName
     * @param string $constraintName
     * @return string
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql) {
        return "ALTER TABLE " . $tableName . " ADD CONSTRAINT " . $constraintName . " " . $sql . ";";
    }

    /**
     * Sentencia SQL para comprobar una secuencia
     * @param string $seqName
     * @return string
     */
    public function sqlSequenceExists($seqName) {
        return "SELECT * FROM pg_class where relname = '" . $seqName . "';";
    }

}
