<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use Exception;
use PDO;

/**
 * Clase para conectar a PostgreSQL utilizando pdo_pgsql.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class PDOPostgresql implements DatabaseEngine
{

    /**
     * Relacion de Transacciones abiertas.
     * @var array
     */
    private $transactions;

    /**
     * Devuelve el motor de base de datos y la versión.
     * @param PDO $link
     * @return string
     */
    public function version($link)
    {
        return 'POSTGRESQL ' . $link->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     * @param array $colData
     * @return array
     */
    public function columnFromData($colData)
    {
        $colData['extra'] = null;

        if ($colData['character_maximum_length'] !== null) {
            $colData['type'] .= '(' . $colData['character_maximum_length'] . ')';
        }

        return $colData;
    }

    /**
     * Conecta a la base de datos.
     * @param string $error
     * @return null|PDO
     */
    public function connect(&$error)
    {
        if (!extension_loaded('pdo_pgsql')) {
            $error = 'No tienes instalada la extensión de PHP para PDO PostgreSQL.';
            return null;
        }

        $conString = 'pgsql:host=' . FS_DB_HOST . ';port=' . FS_DB_PORT.';dbname=' . FS_DB_NAME;
        $options = [
            PDO::ATTR_EMULATE_PREPARES => false,
            //PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        $result = @new PDO($conString, FS_DB_USER, FS_DB_PASS, $options);
        // '00000' significa que es correcto
        if ($result->errorCode() !== '00000') {
            $errors = $result->errorInfo();
            $error = $errors[0] . ' ' . $errors[1] . ' ' . $errors[2];
            return null;
        }
        $this->exec($result, "SET NAMES 'UTF8'");
        //$result->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $this->exec($result, 'SET DATESTYLE TO ISO, DMY;'); /// establecemos el formato de fecha para la conexión

        return $result;
    }

    /**
     * Desconecta de la base de datos.
     * @param PDO $link
     * @return bool
     */
    public function close($link)
    {
        $link->exec('SELECT pg_terminate_backend(pg_backend_pid());');
        $link = null;
        return true;
    }

    /**
     * Devuelve el error de la ultima sentencia ejecutada
     * @param PDO $link
     * @return string
     */
    public function errorMessage($link)
    {
        $errors = $link->errorInfo();
        return $errors[0] . ' ' . $errors[1] . ' ' . $errors[2];
    }

    /**
     * Inicia una transacción SQL.
     * @param PDO $link
     * @return bool
     */
    public function beginTransaction($link)
    {
        $result = $this->exec($link, 'BEGIN TRANSACTION;');
        if ($result) {
            $this->transactions[] = $link;
        }
        return $result;
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @param PDO $link
     * @return bool
     */
    public function commit($link)
    {
        $result = $this->exec($link, 'COMMIT;');
        if ($result && in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @param PDO $link
     * @return bool
     */
    public function rollback($link)
    {
        $result = $this->exec($link, 'ROLLBACK;');
        if (in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Indica si la conexión está en transacción
     * @param PDO $link
     * @return bool
     */
    public function inTransaction($link)
    {
        return in_array($link, $this->transactions, false);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o array vacío en caso de fallo.
     * @param PDO $link
     * @param string $sql
     * @return array
     */
    public function select($link, $sql)
    {
        $result = [];
        try {
            $aux = $link->query($sql);
            if ($aux) {
                $result = [];
                while ($row = $aux->fetchAll(PDO::FETCH_ASSOC)) {
                    $result[] = $row;
                }
                $aux->closeCursor();
            }
        } catch (Exception $e) {
            $result = [];
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos
     * (inserts, updates o deletes).
     * @param PDO $link
     * @param string $sql
     * @return bool
     */
    public function exec($link, $sql)
    {
        try {
            if ($link->exec($sql)) {
                do {
                    $more = $link->nextRowset();
                } while ($more);
            }
            $result = (!$link->errorCode());
        } catch (Exception $e) {
            $result = false;
        }

        return $result;    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param PDO $link
     * @param string $str
     * @return string
     */
    public function escapeString($link, $str)
    {
        return $link->quote($str);
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle()
    {
        return 'd-m-Y';
    }

    /**
     * Devuelve el SQL necesario para convertir
     * la columna a entero.
     * @param string $colName
     * @return string
     */
    public function sql2int($colName)
    {
        return 'CAST(' . $colName . ' as INTEGER)';
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
     * @param string $dbType
     * @param string $xmlType
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        return ($dbType === $xmlType);
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @param PDO $link
     * @return array
     */
    public function listTables($link)
    {
        $tables = [];
        $sql = 'SELECT tablename'
            . ' FROM pg_catalog.pg_tables'
            . " WHERE schemaname NOT IN ('pg_catalog','information_schema')"
            . ' ORDER BY tablename ASC;';

        $aux = $this->select($link, $sql);
        if (!empty($aux)) {
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
     * @param PDO $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        $aux = explode("'", $default);
        if (count($aux) === 3) {
            $data = $this->select($link, $this->sqlSequenceExists($aux[1]));
            if (empty($data)) {             /// ¿Existe esa secuencia?
                $data = $this->select($link, 'SELECT MAX(' . $colname . ')+1 as num FROM ' . $tableName . ';');
                $this->exec($link, 'CREATE SEQUENCE ' . $aux[1] . ' START ' . $data[0]['num'] . ';');
            }
        }
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param PDO $link
     * @param string $tableName
     * @param string $error
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        return true;
    }

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     * @param array $xmlCons
     * @return string
     */
    public function generateTableConstraints($xmlCons)
    {
        $sql = '';

        foreach ($xmlCons as $res) {
            $value = strtolower($res['consulta']);
            if (false !== strpos($value, 'primary key')) {
                $sql .= ', ' . $res['consulta'];
                continue;
            }

            if (FS_FOREIGN_KEYS === '1' || 0 !== strpos($res['consulta'], 'FOREIGN KEY')) {
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
    public function sqlLastValue()
    {
        return 'SELECT lastval() as num;';
    }

    /**
     * Devuelve el SQL para averiguar
     * la lista de las columnas de una tabla.
     * @param string $tableName
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
     * @param string $tableName
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
     * @param string $tableName
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
     * Devuelve el SQL para averiguar
     * la lista de indices de una tabla.
     * @param string $tableName
     * @return string
     */
    public function sqlIndexes($tableName)
    {
        return "SELECT indexname as Key_name FROM pg_indexes WHERE tablename = '" . $tableName . "';";
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $constraints)
    {
        $serials = ['serial', 'bigserial'];
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', ' . $col['nombre'] . ' ' . $col['tipo'];

            if ($col['nulo'] === 'NO') {
                $fields .= ' NOT NULL';
            }

            if (in_array($col['tipo'], $serials, false)) {
                continue;
            }

            if ($col['defecto'] !== '') {
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
    public function sqlAlterAddColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName
            . ' ADD COLUMN ' . $colData['nombre'] . ' ' . $colData['tipo'];

        if ($colData['defecto'] !== '') {
            $sql .= ' DEFAULT ' . $colData['defecto'];
        }

        if ($colData['nulo'] === 'NO') {
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
    public function sqlAlterModifyColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName
            . ' ALTER COLUMN ' . $colData['nombre'] . ' TYPE ' . $colData['tipo'];
        return $sql . ';';
    }

    /**
     * Sentencia SQL para modificar un valor por defecto de un campo de una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterConstraintDefault($tableName, $colData)
    {
        $action = ($colData['defecto'] !== '') ? ' SET DEFAULT ' . $colData['defecto'] : ' DROP DEFAULT';

        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['nombre'] . $action . ';';
    }

    /**
     * Sentencia SQL para modificar una constraint null de un campo de una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlAlterConstraintNull($tableName, $colData)
    {
        $action = ($colData['nulo'] === 'YES') ? ' DROP ' : ' SET ';
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['nombre'] . $action . 'NOT NULL;';
    }

    /**
     * Sentencia SQL para eliminar una constraint a una tabla
     * @param string $tableName
     * @param array $colData
     * @return string
     */
    public function sqlDropConstraint($tableName, $colData)
    {
        return 'ALTER TABLE ' . $tableName . ' DROP CONSTRAINT ' . $colData['name'] . ';';
    }

    /**
     * Sentencia SQL para añadir una constraint a una tabla
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     * @return string
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql)
    {
        return 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $constraintName . ' ' . $sql . ';';
    }

    /**
     * Sentencia SQL para comprobar una secuencia
     * @param string $seqName
     * @return string
     */
    public function sqlSequenceExists($seqName)
    {
        return "SELECT * FROM pg_class where relname = '" . $seqName . "';";
    }
}
