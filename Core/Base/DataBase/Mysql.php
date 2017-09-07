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
use mysqli;

/**
 * Clase para conectar a MySQL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Mysql implements DataBaseEngine
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
     * Relacion de Transacciones abiertas.
     *
     * @var array
     */
    private $transactions;

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
        $this->utilsSQL = new MysqlSQL();
        $this->transactions = [];
        $this->lastErrorMsg = '';
    }

    /**
     * Destructor de la clase
     */
    public function __destruct()
    {
        $this->rollbackTransactions();
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
     * @param mysqli $link
     */
    private function unsetTransaction($link)
    {
        $count = 0;
        foreach ($this->transactions as $trans) {
            if ($trans === $link) {
                array_splice($this->transactions, $count, 1);
                break;
            }
            ++$count;
        }
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     *
     * @param mysqli $link
     *
     * @return string
     */
    public function version($link)
    {
        return 'MYSQL ' . $link->server_version;
    }

    /**
     * Conecta a la base de datos.
     *
     * @param string $error
     *
     * @return null|mysqli
     */
    public function connect(&$error)
    {
        if (!class_exists('mysqli')) {
            $error = 'No tienes instalada la extensión de PHP para MySQL.';

            return null;
        }

        $result = new \mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, (int) FS_DB_PORT);
        if ($result->connect_errno) {
            $error = $result->connect_error;
            $this->lastErrorMsg = $error;

            return null;
        }

        $result->set_charset('utf8');
        $result->autocommit(false);

        /// desactivamos las claves ajenas
        if (FS_FOREIGN_KEYS !== '1') {
            $this->exec($result, 'SET foreign_key_checks = 0;');
        }

        return $result;
    }

    /**
     * Desconecta de la base de datos.
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function close($link)
    {
        $this->rollbackTransactions();

        return $link->close();
    }

    /**
     * Devuelve el error de la ultima sentencia ejecutada
     *
     * @param mysqli $link
     *
     * @return string
     */
    public function errorMessage($link)
    {
        return ($link->error != '') ? $link->error : $this->lastErrorMsg;
    }

    /**
     * Inicia una transacción SQL.
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function beginTransaction($link)
    {
        $result = $this->exec($link, 'START TRANSACTION;');
        if ($result) {
            $this->transactions[] = $link;
        }

        return $result;
    }

    /**
     * Guarda los cambios de una transacción SQL.
     *
     * @param mysqli $link
     *
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
     * TODO
     *
     * @param mysqli $link
     *
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
     *
     * @param mysqli $link
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
     * @param mysqli $link
     * @param string $sql
     *
     * @return array
     */
    public function select($link, $sql)
    {
        $result = [];
        try {
            $aux = $link->query($sql);
            if ($aux) {
                $result = [];
                while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
                    $result[] = $row;
                }
                $aux->free();
            }
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $result = [];
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos
     * (inserts, updates o deletes)
     *
     * @param mysqli $link
     * @param string $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        try {
            if ($link->multi_query($sql)) {
                do {
                    $more = ($link->more_results() && $link->next_result());
                } while ($more);
            }
            $result = (!$link->errno);
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $result = false;
        }

        return $result;
    }

    /**
     * Escapa las comillas de la cadena de texto.
     *
     * @param mysqli $link
     * @param string $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        return $link->escape_string($str);
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     *
     * @return string
     */
    public function dateStyle()
    {
        return 'Y-m-d';
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
        return (0 === strpos($dbType, 'int(') && $xmlType === 'INTEGER') ||
            (0 === strpos($dbType, 'double') && $xmlType === 'double precision');
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
        $result = 0 === strpos($xmlType, 'character varying(');
        if ($result) {
            $result = (0 === strpos($dbType, 'varchar(')) || (0 === strpos($dbType, 'char('));
        }

        return $result;
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
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
            ($dbType === 'tinyint(1)' && $xmlType === 'boolean') ||
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
     * @param mysqli $link
     *
     * @return array
     */
    public function listTables($link)
    {
        $tables = [];
        $aux = $this->select($link, 'SHOW TABLES;');
        if (!empty($aux)) {
            foreach ($aux as $a) {
                $key = 'Tables_in_' . FS_DB_NAME;
                if (isset($a[$key])) {
                    $tables[] = $a[$key];
                }
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
     * @param mysqli $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     *
     * @return bool
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        return true;
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     *
     * @param mysqli $link
     * @param string $tableName
     * @param string $error
     *
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        $result = true;

        /// ¿La tabla no usa InnoDB?
        $data = $this->select($link, 'SHOW TABLE STATUS FROM `' . FS_DB_NAME . "` LIKE '" . $tableName . "';");
        if (!empty($data) && $data[0]['Engine'] !== 'InnoDB') {
            $result = $this->exec($link, 'ALTER TABLE ' . $tableName . ' ENGINE=InnoDB;');
            if ($result) {
                $error = 'Imposible convertir la tabla ' . $tableName . ' a InnoDB.'
                    . ' Imprescindible para FacturaScripts.';
            }
        }

        return $result;
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
        $result = array_change_key_case($colData);
        $result['is_nullable'] = $result['null'];
        $result['name'] = $result['field'];

        unset($result['null'], $result['field']);

        return $result;
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
