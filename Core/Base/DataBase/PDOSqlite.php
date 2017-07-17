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

use PDO;
use PDOException;
use PDOStatement;

/**
 * Clase para conectar a SQLite utilizando pdo_sqlite.
 * Puede considerarse en estado alpha, falta completar el fixPostgresql y derivados
 * y probarlo a fondo.
 *
 * Basado en: http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class PDOSqlite implements DatabaseEngine
{
    /**
     * Database Handler
     * @var PDO
     */
    private $dbh;
    /**
     * Errores devueltos
     * @var string
     */
    private $error;
    /**
     * Contiene la declaración
     * @var PDOStatement|PDOException
     */
    private $stmt;
    /**
     * Relacion de Transacciones abiertas.
     * @var array
     */
    private $transactions;

    /**
     * Contructor e inicializador de la clase
     */
    public function __construct()
    {
        $this->transactions = [];
    }

    /**
     * Se intenta realizar la conexión a la base de datos SQLite,
     * si se ha realizado se devuelve true, sino false.
     * En el caso que sea false, $errors contiene el error
     *
     * @param $errors
     * @param $dbData
     *
     * @return bool
     */
    public static function testConnect(&$errors, $dbData)
    {
        $dsnHost = 'sqlite:facturascripts.db';
        $options = [
            PDO::ATTR_EMULATE_PREPARES => 1,
            //            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Creamos una nueva instancia PDO
        try {
            $connection = new PDO($dsnHost, $dbData['user'], $dbData['pass'], $options);
            if ($connection !== null && $connection->errorCode() === '00000') {
                $errors = [];
                return true;
            }
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Destructor de la clase
     */
    public function __destruct()
    {
        $this->rollbackTransactions();
    }

    /**
     * Conecta a la base de datos.
     *
     * @param string $error
     *
     * @return null|PDO
     */
    public function connect(&$error)
    {
        if (!extension_loaded('pdo')) {
            $this->error = 'No tienes instalada la extensión de PHP para PDO.';
            $error = $this->error;
            return null;
        }
        if (!extension_loaded('pdo_sqlite')) {
            $this->error = 'No tienes instalada la extensión de PHP para PDO SQLite.';
            $error = $this->error;
            return null;
        }

        $dsn = 'sqlite:facturascripts.db';
        $options = [
            PDO::ATTR_EMULATE_PREPARES => 1,
            //            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Creamos una nueva instancia PDO
        try {
            $this->dbh = new PDO($dsn, FS_DB_USER, FS_DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $error = $this->error;
            return null;
        }

        // Force SQLite to use the UTF-8 character set by default.
        //$this->dbh->exec('PRAGMA encoding "UTF-8";');

        /// Desactivamos las claves ajenas
        if (FS_FOREIGN_KEYS !== '1') {
            $this->dbh->exec('PRAGMA foreign_keys = 0;');
        }

        return $this->dbh;
    }

    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param string $queryString <p>This must be a valid SQL statement for the target database server.</p>
     */
    public function query($queryString)
    {
        $this->stmt = $this->dbh->prepare($queryString);
    }

    /**
     * Binds a value to a parameter
     *
     * @param mixed $param <p>Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.</p>
     * @param mixed $value <p>The value to bind to the parameter.</p>
     * @param int $type [optional] <p>Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     */
    public function bind($param, $value, $type = PDO::PARAM_STR)
    {
        if ($type === null) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case ($value === null):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Executes a prepared statement
     *
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * Returns an array containing all of the result set rows
     *
     * @return array <b>PDOStatement::fetchAll</b> returns an array containing
     * all of the remaining rows in the result set. The array represents each
     * row as either an array of column values or an object with properties
     * corresponding to each column name.
     */
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the next row from a result set
     *
     * @return mixed The return value of this function on success depends on the fetch type. In
     * all cases, <b>FALSE</b> is returned on failure.
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     *
     * @return int the number of rows.
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @return string representing the row ID of the last row that was inserted into the database.
     */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Initiates a transaction
     *
     * @param PDO $link
     *
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction($link)
    {
        $result = $this->dbh->beginTransaction();
        if ($result) {
            $this->transactions[] = $link;
        }
        return $result;
    }

    /**
     * Commits a transaction
     *
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    /**
     * Rolls back a transaction
     *
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }

    /**
     * Dump an SQL prepared command
     *
     * @return bool No value is returned.
     */
    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function generateTableConstraints($xmlCons)
    {
        $sql = '';
        foreach ($xmlCons as $res) {
            $sql .= ', CONSTRAINT ' . $res['nombre'] . ' ' . $res['consulta'];
        }

        return $this->fixPostgresql($sql);
    }

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     *
     * @param array $colData
     *
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
     * Información sobre el motor de base de datos
     *
     * @param PDO $link
     *
     * @return string
     */
    public function version($link)
    {
        return 'SQLITE ' . $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Cierra la conexión con la base de datos
     *
     * @param PDO $link
     */
    public function close($link)
    {
        if ($this->dbh) {
            $this->cancelTransaction();
            $this->stmt->closeCursor();
            $this->dbh = null;
        }
    }

    /**
     * Último mensaje de error generado un operación con la BD
     *
     * @param PDO $link
     *
     * @return string
     */
    public function errorMessage($link)
    {
        return $this->error;
    }

    /**
     * Confirma las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function commit($link)
    {
        $result = $this->endTransaction();
        if ($result && in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Deshace las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function rollback($link)
    {
        $result = $this->cancelTransaction();
        if (in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Indica si la conexión tiene una transacción abierta
     *
     * @param PDO $link
     *
     * @return bool
     */
    public function inTransaction($link)
    {
        return in_array($link, $this->transactions, false);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o array vacío en caso de fallo.
     *
     * @param PDO $link
     * @param string $sql
     *
     * @return array
     */
    public function select($link, $sql)
    {
        $result = [];
        $this->query($sql);
        $aux = $this->resultSet();
        if (!empty($aux)) {
            foreach ($aux as $row) {
                $result[] = $row;
            }
        }
        unset($aux);
        return $result;
    }

    /**
     * Ejecuta una sentencia DDL sobre la conexión.
     * Si no hay transacción abierta crea una y la finaliza
     *
     * @param PDO $link
     * @param string $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        $this->stmt = $this->dbh->prepare($sql);
        return $this->stmt->execute();
    }

    /**
     * Compara las columnas indicadas en los arrays
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        $result = (
            ($dbType === $xmlType) ||
            ($dbType === 'INTEGER(1)' && $xmlType === 'boolean') ||
            (substr($dbType, 8, -1) === substr($xmlType, 18, -1)) ||
            (substr($dbType, 5, -1) === substr($xmlType, 18, -1))
        );

        if (!$result) {
            $result = $this->compareDataTypeNumeric($dbType, $xmlType);
        }

        if (!$result) {
            $result = $this->compareDataTypeChar($dbType, $xmlType);
        }

        return $result;
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     *
     * @param PDO $link
     *
     * @return array
     */
    public function listTables($link)
    {
        $tables = [];
        $sql = 'SELECT name FROM sqlite_master;';

        $aux = $this->select($link, $sql);
        if (!empty($aux)) {
            foreach ($aux as $a) {
                $tables[] = $a['name'];
            }
        }
        return $tables;
    }

    /**
     * Escapa la cadena indicada
     *
     * @param PDO $link
     * @param string $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        //return $this->dbh->quote($str);
        return $str;
    }

    /**
     * Indica el formato de fecha que utiliza la BD
     *
     * @return string
     */
    public function dateStyle()
    {
        return 'd-m-Y';
    }

    /**
     * Indica el SQL a usar para convertir la columna en Integer
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2int($colName)
    {
        return 'CAST(' . $colName . ' as INTEGER)';
    }

    /**
     * Comprueba la existencia de una secuencia
     * A partir del campo default de una tabla
     * comprueba si se refiere a una secuencia, y si es así
     * comprueba la existencia de la secuencia. Si no la encuentra
     * la crea.
     *
     * @param PDO $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     *
     * @return bool
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        $aux = explode("'", $default);
        if (count($aux) === 3) {
            $data = $this->dbh->query($this->sqlSequenceExists($aux[1]));
            if ($data) {             /// ¿Existe esa secuencia?
                $data = $this->dbh->query('SELECT MAX(' . $colname . ')+1 as num FROM ' . $tableName . ';');
                $this->dbh->exec('CREATE SEQUENCE ' . $aux[1] . ' START ' . $data[0]['num'] . ';');
            }
        }
        return true;
    }

    /**
     * Comprobación adicional a la existencia de una tabla
     *
     * @param PDO $link
     * @param string $tableName
     * @param string $error
     *
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        return true;
    }

    /**
     * Sentencia SQL para obtener el último valor de una secuencia o ID
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return $this->lastInsertId();
    }

    /**
     * Sentencia SQL para obtener las columnas de una tabla
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlColumns($tableName)
    {
        // TODO: Comprobar que sea realmente así
        return 'DESCRIBE ' . $tableName;
    }

    /**
     * Sentencia SQL para obtener las constraints de una tabla
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints($tableName)
    {
        // TODO: Este no es su equivalente
        $sql = 'SELECT tc.constraint_type as type, tc.constraint_name as name'
            . ' FROM information_schema.table_constraints AS tc'
            . " WHERE tc.table_name = '" . $tableName . "'"
            . " AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')"
            . ' ORDER BY 1 DESC, 2 ASC;';
        return $sql;
    }

    /**
     * Sentencia SQL para obtener las constraints (extendidas) de una tabla
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended($tableName)
    {
        // TODO: Este no es su equivalente
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
     * Sentencia SQL para obtener los indices de una tabla
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlIndexes($tableName)
    {
        // TODO: Comprobar que sea realmente así
        return 'SHOW INDEXES FROM ' . $tableName . ';';
    }

    /**
     * Sentencia SQL para crear una tabla
     *
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     *
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $constraints)
    {
        $fields = '';
        foreach ($columns as $col) {
            $fields .= ', `' . $col['nombre'] . '` ' . $this->getTypeAndConstraints($col);
        }

        $sql = $this->fixPostgresql(substr($fields, 2));
        $result = 'CREATE TABLE ' . $tableName . ' (' . $sql
            . $this->generateTableConstraints($constraints) . ');';
        return $result;
    }

    /**
     * Sentencia SQL para añadir una columna a una tabla
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName . ' ADD `' . $colData['nombre'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';

        return $sql;
    }

    /**
     * Sentencia SQL para modificar la definición de una columna de una tabla
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData)
    {
        $sql = 'ALTER TABLE ' . $tableName
            . ' MODIFY `' . $colData['nombre'] . '` '
            . $this->getTypeAndConstraints($colData) . ';';

        return $this->fixPostgresql($sql);
    }

    /**
     * Sentencia SQL para modificar valor por defecto de una columna de una tabla
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterConstraintDefault($tableName, $colData)
    {
        $action = ($colData['defecto'] !== '') ? ' SET DEFAULT ' . $colData['defecto'] : ' DROP DEFAULT';

        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['nombre'] . $action . ';';
    }

    /**
     * Sentencia SQL para modificar un constraint null de una columna de una tabla
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterConstraintNull($tableName, $colData)
    {
        $action = ($colData['nulo'] === 'YES') ? ' DROP ' : ' SET ';
        return 'ALTER TABLE ' . $tableName . ' ALTER COLUMN ' . $colData['nombre'] . $action . 'NOT NULL;';
    }

    /**
     * Sentencia SQL para eliminar una constraint de una tabla
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlDropConstraint($tableName, $colData)
    {
        return 'ALTER TABLE ' . $tableName . ' DROP CONSTRAINT ' . $colData['name'] . ';';
    }

    /**
     * Sentencia SQL para añadir una constraint de una tabla
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
     * Sentencia para crear una secuencia
     *
     * @param string $seqName
     *
     * @return string
     */
    public function sqlSequenceExists($seqName)
    {
        return '';
    }

    /**
     * Devuelve el tipo de conexión que utiliza
     * @return string
     */
    public function getType()
    {
        return 'pdo_sqlite';
    }

    /**
     * Deshace todas las transacciones activas
     */
    private function rollbackTransactions()
    {
        foreach ($this->transactions as $link) {
            $this->rollback($link);
        }
    }

    /**
     * Borra de la lista la transaccion indicada
     *
     * @param PDO $link
     */
    private function unsetTransaction($link)
    {
        $count = 0;
        foreach ($this->transactions as $trans) {
            if ($trans === $link) {
                array_splice($this->transactions, $count, 1);
                break;
            }
            $count++;
        }
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
     * TODO
     *
     * @param array $colData
     *
     * @return string
     */
    private function getConstraints($colData)
    {
        $notNull = ($colData['nulo'] === 'NO');
        $result = ' NULL';
        if ($notNull) {
            $result = ' NOT' . $result;
        }

        $defaultNull = ($colData['defecto'] === null);
        if ($defaultNull && !$notNull) {
            $result .= ' DEFAULT NULL';
        } else {
            if ($colData['defecto'] !== '') {
                if ($colData['defecto'] !== 'true' && $colData['defecto'] !== 'false') {
                    $result .= ' DEFAULT ' . $colData['defecto'];
                } elseif ($colData['defecto'] !== 'true') {
                    $result .= ' DEFAULT 1';
                } elseif ($colData['defecto'] !== 'false') {
                    $result .= ' DEFAULT 0';
                }
            }
        }

        return $result;
    }

    /**
     * Genera el SQL con el tipo de campo y las constraints DEFAULT y NULL
     * https://sqlite.org/datatype3.html
     *
     * @param array $colData
     *
     * @return string
     */
    private function getTypeAndConstraints($colData)
    {
        $type = stripos('boolean,integer,serial',
            $colData['tipo']) === false ? strtolower($colData['tipo']) : FS_DB_INTEGER;
        switch (true) {
            case ($type === 'serial'):
            case (stripos($colData['defecto'], 'nextval(') !== false):
                $contraints = ' NOT NULL AUTO_INCREMENT';
                break;
            case (stripos($colData['defecto'], 'boolean') !== false):
                $contraints = ' NOT NULL AUTO_INCREMENT';
                break;
            default:
                $contraints = $this->getConstraints($colData);
                break;
        }
        return ' ' . $type . $contraints;
    }

    /**
     * Compara los tipos de datos de una columna numerica.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypeNumeric($dbType, $xmlType)
    {
        switch (strtolower($xmlType)) {
            case 'integer':
                $types = [
                    'INT',
                    'INTEGER',
                    'TINYINT',
                    'SMALLINT',
                    'MEDIUMINT',
                    'BIGINT',
                    'UNSIGNED BIG INT',
                    'INT2',
                    'INT8'
                ];
                break;
            case 'double precision':
                $types = [
                    'REAL',
                    'DOUBLE',
                    'DOUBLE PRECISION',
                    'FLOAT',
                    'NUMERIC',
                    'DECIMAL'
                ];
                break;
            case 'timestamp':
            case 'date':
            case 'datetime':
                $types = [
                    'DATE',
                    'DATETIME'
                ];
                break;
            default:
                $types = [];
        }
        return in_array($dbType, $types,false);
    }

    /**
     * Compara los tipos de datos de una columna alfanumerica.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypeChar($dbType, $xmlType)
    {
        switch (strtolower($xmlType)) {
            case 'character varying(':
                $types = [
                    'CHARACTER(',
                    'VARCHAR(',
                    'VARYING CHARACTER(',
                    'NCHAR(',
                    'NATIVE CHARACTER(',
                    'NVARCHAR(',
                    'TEXT',
                    'CLOB'
                ];
                break;
            default:
                $types = [];
        }
        return in_array($dbType, $types, false);
    }
}
