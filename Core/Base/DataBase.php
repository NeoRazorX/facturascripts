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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseEngine;
use FacturaScripts\Core\Base\DataBase\Mysql;
use FacturaScripts\Core\Base\DataBase\Postgresql;

/**
 * Clase genérica de acceso a la base de datos, ya sea MySQL o PostgreSQL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBase
{

    /**
     * El enlace con la base de datos.
     *
     * @var resource
     */
    private static $link;

    /**
     * Enlace al motor de base de datos seleccionado en la configuración
     *
     * @var DataBaseEngine
     */
    private static $engine;

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     *
     * @var MiniLog
     */
    private static $miniLog;

    /**
     * Lista de tablas de la base de datos
     *
     * @var array
     */
    private static $tables;

    /**
     * Construye y prepara la clase para su uso
     */
    public function __construct()
    {
        if (self::$link === null) {
            self::$miniLog = new MiniLog();
            self::$tables = [];

            switch (strtolower(FS_DB_TYPE)) {
                case 'mysql':
                    self::$engine = new Mysql();
                    break;

                case 'postgresql':
                    self::$engine = new Postgresql();
                    break;

                default:
                    self::$engine = null;
                    $i18n = new Translator();
                    self::$miniLog->critical($i18n->trans('db-type-not-recognized'));
                    break;
            }
        }
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     *
     * @return array
     */
    public function getTables()
    {
        if (count(self::$tables) === 0) {
            self::$tables = self::$engine->listTables(self::$link);
        }

        return self::$tables;
    }

    /**
     * Devuelve un array con las columnas de una tabla dada.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getColumns($tableName)
    {
        $result = [];
        $data = $this->select(self::$engine->getSQL()->sqlColumns($tableName));
        if (is_array($data) && !empty($data)) {
            foreach ($data as $dataCol) {
                $column = self::$engine->columnFromData($dataCol);
                $result[$column['name']] = $column;
            }
        }

        return $result;
    }

    /**
     * Devuelve una array con las restricciones de una tabla.
     *
     * @param string $tableName
     * @param bool   $extended
     *
     * @return array
     */
    public function getConstraints($tableName, $extended = false)
    {
        if ($extended) {
            $sql = self::$engine->getSQL()->sqlConstraintsExtended($tableName);
        } else {
            $sql = self::$engine->getSQL()->sqlConstraints($tableName);
        }

        $data = $this->select($sql);

        return $data ? array_values($data) : [];
    }

    /**
     * Devuelve una array con los indices de una tabla dada.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getIndexes($tableName)
    {
        $result = [];
        $data = $this->select(self::$engine->getSQL()->sqlIndexes($tableName));
        if (is_array($data) && !empty($data)) {
            foreach ($data as $row) {
                $result[] = ['name' => $row['Key_name']];
            }
        }

        return $result;
    }

    /**
     * Devuelve True si se está conestado a la base de datos.
     *
     * @return bool
     */
    public function connected()
    {
        return (bool) self::$link;
    }

    /**
     * Conecta a la base de datos.
     *
     * @return bool
     */
    public function connect()
    {
        if ($this->connected()) {
            return true;
        }

        $error = '';
        self::$link = self::$engine->connect($error);

        if ($error !== '') {
            self::$miniLog->critical($error);
        }

        return $this->connected();
    }

    /**
     * Desconecta de la base de datos.
     *
     * @return bool
     */
    public function close()
    {
        if (!$this->connected()) {
            return true;
        }

        if (self::$engine->inTransaction(self::$link) && !$this->rollback()) {
            return false;
        }

        if (self::$engine->close(self::$link)) {
            self::$link = null;
        }

        return !$this->connected();
    }

    /**
     * Indica hay una transacción abierta
     *
     * @return bool
     */
    public function inTransaction()
    {
        return self::$engine->inTransaction(self::$link);
    }

    /**
     * Inicia una transaccion en la base de datos
     *
     * @return bool
     */
    public function beginTransaction()
    {
        $result = $this->inTransaction();
        if (!$result) {
            self::$miniLog->sql('Begin Transaction');
            $result = self::$engine->beginTransaction(self::$link);
        }

        return $result;
    }

    /**
     * Graba las sentencias ejecutadas en la base de datos
     *
     * @return bool
     */
    public function commit()
    {
        $result = self::$engine->commit(self::$link);
        if ($result) {
            self::$miniLog->sql('Commit Transaction');
        }

        return $result;
    }

    /**
     * Deshace las sentencias ejecutadas en la base de datos
     *
     * @return bool
     */
    public function rollback()
    {
        self::$miniLog->error(self::$engine->errorMessage(self::$link));
        self::$miniLog->sql('Rollback Transaction');

        return self::$engine->rollback(self::$link);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     *
     * @param string $sql
     *
     * @return array
     */
    public function select($sql)
    {
        return $this->selectLimit($sql, 0, 0);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados o array vació en caso de fallo.
     * Limit es el número de elementos que quieres que devuelva.
     * Offset es el número de resultado desde el que quieres que empiece.
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function selectLimit($sql, $limit = FS_ITEM_LIMIT, $offset = 0)
    {
        if (!$this->connected()) {
            return [];
        }

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';'; /// añadimos limit y offset a la consulta sql
        }

        self::$miniLog->sql($sql); /// añadimos la consulta sql al historial
        $result = self::$engine->select(self::$link, $sql);
        if (empty($result)) {
            self::$miniLog->critical(self::$engine->errorMessage(self::$link));

            return [];
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates o deletes).
     * Para hacer selects, mejor usar select() o selecLimit().
     * Si no hay transacción abierta se inicia una, se ejecutan las consultas
     * Si la transaccion la ha abierto en la llamada la cierra confirmando o descartando
     * según haya ido bien o haya dado algún error
     *
     * @param string $sql
     *
     * @return bool
     */
    public function exec($sql)
    {
        $result = $this->connected();
        if ($result) {
            self::$tables = []; /// limpiamos la lista de tablas, ya que podría haber cambios al ejecutar este sql.

            $inTransaction = $this->inTransaction();
            $this->beginTransaction();

            self::$miniLog->sql($sql); /// añadimos la consulta sql al historial
            $result = self::$engine->exec(self::$link, $sql);
            if (!$inTransaction) { /// Sólo operamos si la transacción la hemos iniciado en esta llamada
                if ($result) {
                    $result = $this->commit();
                } else {
                    $this->rollback();
                }
            }
        }

        return (bool) $result;
    }

    /**
     * Devuelve el último ID asignado al hacer un INSERT
     * en la base de datos.
     *
     * @return integer|bool
     */
    public function lastval()
    {
        $aux = $this->select(self::$engine->getSQL()->sqlLastValue());

        return $aux ? $aux[0]['num'] : false;
    }

    /**
     * Devuelve el motor de base de datos usado y la versión.
     *
     * @return string
     */
    public function version()
    {
        if (!$this->connected()) {
            return '';
        }

        return self::$engine->version(self::$link);
    }

    /**
     * Devuelve True si la tabla existe, False en caso contrario.
     *
     * @param string $tableName
     * @param array  $list
     *
     * @return bool
     */
    public function tableExists($tableName, array $list = [])
    {
        if (empty($list)) {
            $list = $this->getTables();
        }

        return in_array($tableName, $list, false);
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function checkTableAux($tableName)
    {
        $error = '';
        $result = self::$engine->checkTableAux(self::$link, $tableName, $error);
        if (!$result) {
            self::$miniLog->critical($error);
        }

        return $result;
    }

    /**
     * Transforma una variable en una cadena de texto válida para ser
     * utilizada en una consulta SQL.
     *
     * @param mixed $val
     *
     * @return string
     */
    public function var2str($val)
    {
        if ($val === null) {
            return 'NULL';
        }

        if (is_bool($val)) {
            if ($val) {
                return 'TRUE';
            }

            return 'FALSE';
        }

        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i", $val)) {
            return "'" . date($this->dateStyle(), strtotime($val)) . "'"; /// es una fecha
        }

        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i", $val)) {
            return "'" . date($this->dateStyle() . ' H:i:s', strtotime($val)) . "'"; /// es una fecha+hora
        }

        return "'" . $this->escapeString($val) . "'";
    }

    /**
     * Escapa las comillas de la cadena de texto.
     *
     * @param string $str
     *
     * @return string
     */
    public function escapeString($str)
    {
        if (self::$engine) {
            $str = self::$engine->escapeString(self::$link, $str);
        }

        return $str;
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     *
     * @return string
     */
    public function dateStyle()
    {
        return self::$engine->dateStyle();
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
        return self::$engine->getSQL()->sql2Int($colName);
    }

    /**
     * 
     * @return DataBaseEngine
     */
    public function getEngine()
    {
        return self::$engine;
    }
}
