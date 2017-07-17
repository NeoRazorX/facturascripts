<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra  francesc.pineda.segarra@gmail.com
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

namespace FacturaScripts\Core\Base\DataBase\DataCollector;

use FacturaScripts\Core\Base\DataBase\Mysql;
use mysqli;

/**
 * Clase para tracear a Mysql.
 * De momento no se utiliza, lo ideal sería utilizarlo conjuntamente
 * con MysqlCollector y obtener todos los detalles, como con PDO.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * Info relaticionada, WP ya utiliza esto con mysqli, así que nos sirve de ejemplo:
 * @source https://github.com/maximebf/php-debugbar/issues/326
 * @source https://github.com/snowair/phalcon-debugbar/blob/master/src/Phalcon/Db/Profiler.php
 * @source https://github.com/WordPress/WordPress/blob/4.8-branch/wp-includes/wp-db.php
 * @source https://github.com/maximebf/php-debugbar/issues/213
 */
class TraceableMysql extends Mysql
{
    /**
     * @var Mysql
     */
    protected $link;
    /**
     * @var array
     */
    protected $executedStatements = [];

    /**
     * TraceableMysql constructor.
     *
     * @param Mysql $link
     */
    public function __construct(Mysql $link)
    {
        parent::__construct();
        $this->link = $link;
    }

    /**
     * Initiates a transaction
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function beginTransaction($link)
    {
        return $this->link->beginTransaction($link);
    }

    /**
     * Commits a transaction
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function commit($link)
    {
        return $this->link->commit($link);
    }

    /**
     * Execute an SQL statement and return the number of affected rows
     *
     * @param mysqli $link
     * @param string $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        return $this->link->exec($link, $sql);
    }

    /**
     * Checks if inside a transaction
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function inTransaction($link)
    {
        return $this->link->inTransaction($link);
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @return string
     */
    public function sqlLastValue()
    {
        return $this->link->sqlLastValue();
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param mysqli $link
     * @param string $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        return $this->link->escapeString($link, $str);
    }

    /**
     * Rolls back a transaction
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function rollback($link)
    {
        return $this->link->rollback($link);
    }

    /**
     * Magic getter
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->link->$name;
    }

    /**
     * Magic setter
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->link->$name = $value;
    }

    /**
     * Magic isset
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (isset($this->link->$name)) {
            return true;
        }
        return false;
    }

    /**
     * @param $name
     * @param $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->link, $name], $args);
    }
}
