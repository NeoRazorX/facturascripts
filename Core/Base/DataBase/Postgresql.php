<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Clase para conectar a PostgreSQL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Postgresql implements DataBaseEngine
{
    /**
     * El enlace con las utilidades comunes entre motores de base de datos.
     *
     * @var DataBaseUtils
     */
    private $utils;

    /**
     * Enlace al conjunto de sentencias SQL de la base de datos conectada
     *
     * @var DataBaseSQL;
     */
    private $utilsSQL;

    /**
     * Ultimo mensaje de error
     *
     * @var string
     */
    private $lastErrorMsg;

    /**
     * Contructor e inicializador de la clase
     */
    public function __construct()
    {
        $this->utils = new DataBaseUtils($this);
        $this->utilsSQL = new PostgresqlSQL();
        $this->lastErrorMsg = '';
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     *
     * @param resource $link
     *
     * @return string
     */
    public function version($link)
    {
        return 'POSTGRESQL ' . pg_version($link)['server'];
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
     * Conecta a la base de datos.
     *
     * @param string $error
     *
     * @return bool|null
     */
    public function connect(&$error)
    {
        if (!function_exists('pg_connect')) {
            $error = 'No tienes instalada la extensión de PHP para PostgreSQL.';

            return null;
        }

        $string = 'host=' . FS_DB_HOST . ' dbname=' . FS_DB_NAME . ' port=' . FS_DB_PORT
            . ' user=' . FS_DB_USER . ' password=' . FS_DB_PASS;
        $result = pg_connect($string);
        if (!$result) {
            $error = pg_last_error();

            return null;
        }

        $this->exec($result, 'SET DATESTYLE TO ISO, DMY;'); /// establecemos el formato de fecha para la conexión

        return $result;
    }

    /**
     * Desconecta de la base de datos.
     *
     * @param resource $link
     *
     * @return bool
     */
    public function close($link)
    {
        return pg_close($link);
    }

    /**
     * Devuelve el error de la ultima sentencia ejecutada
     *
     * @param resource $link
     *
     * @return string
     */
    public function errorMessage($link)
    {
        $error = pg_last_error($link);

        return ($error != '') ? $error : $this->lastErrorMsg;
    }

    /**
     * Inicia una transacción SQL.
     *
     * @param resource $link
     *
     * @return bool
     */
    public function beginTransaction($link)
    {
        return $this->exec($link, 'BEGIN TRANSACTION;');
    }

    /**
     * Guarda los cambios de una transacción SQL.
     *
     * @param resource $link
     *
     * @return bool
     */
    public function commit($link)
    {
        return $this->exec($link, 'COMMIT;');
    }

    /**
     * Deshace los cambios de una transacción SQL.
     *
     * @param resource $link
     *
     * @return bool
     */
    public function rollback($link)
    {
        return $this->exec($link, 'ROLLBACK;');
    }

    /**
     * Indica si la conexión está en transacción
     *
     * @param resource $link
     *
     * @return bool
     */
    public function inTransaction($link)
    {
        $status = pg_transaction_status($link);
        switch ($status) {
            case PGSQL_TRANSACTION_ACTIVE:
            case PGSQL_TRANSACTION_INTRANS:
            case PGSQL_TRANSACTION_INERROR:
                $result = true;
                break;

            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL y devuelve un array con los resultados en
     * caso de $selectRows = true, o array vacío en caso de fallo.
     *
     * @param resource $link
     * @param string   $sql
     * @param bool     $selectRows
     *
     * @return array
     */
    private function runSql($link, $sql, $selectRows = true)
    {
        $result = [];
        try {
            $aux = pg_query($link, $sql);
            if ($aux) {
                if ($selectRows) {
                    $result = pg_fetch_all($aux);
                }
                pg_free_result($aux);
            }
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $result = $selectRows ? [] : ['ok' => 'false'];
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select
     *
     * @param resource $link
     * @param string   $sql
     *
     * @return resource
     */
    public function select($link, $sql)
    {
        return $this->runSql($link, $sql);
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos
     * (inserts, updates o deletes).
     *
     * @param resource $link
     * @param string   $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        return empty($this->runSql($link, $sql, false));
    }

    /**
     * Escapa las comillas de la cadena de texto.
     *
     * @param resource $link
     * @param string   $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        return pg_escape_string($link, $str);
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     *
     * @return string
     */
    public function dateStyle()
    {
        return 'd-m-Y';
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve True si son iguales.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        return $dbType === $xmlType;
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     *
     * @param resource $link
     *
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
     *
     * @param resource $link
     * @param string   $tableName
     * @param string   $default
     * @param string   $colname
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        $aux = explode("'", $default);
        if (count($aux) === 3) {
            $data = $this->select($link, $this->utilsSQL->sqlSequenceExists($aux[1]));
            if (empty($data)) {             /// ¿Existe esa secuencia?
                $data = $this->select($link, 'SELECT MAX(' . $colname . ')+1 as num FROM ' . $tableName . ';');
                $this->exec($link, 'CREATE SEQUENCE ' . $aux[1] . ' START ' . $data[0]['num'] . ';');
            }
        }
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     *
     * @param resource $link
     * @param string   $tableName
     * @param string   $error
     *
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        return true;
    }

    /**
     * Devuelve el enlace a la clase de Utilidades del engine
     *
     * @return DataBaseUtils
     */
    public function getUtils()
    {
        return $this->utils;
    }

    /**
     * Devuelve el enlace a la clase de SQL del engine
     *
     * @return DataBaseSQL
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }
}
