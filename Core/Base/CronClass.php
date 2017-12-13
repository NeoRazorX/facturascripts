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

namespace FacturaScripts\Core\Base;

/**
 * Defines global attributes and methos for all classes.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José Tovar
 */
abstract class CronClass
{
    /**
     * Cache object.
     *
     * @var Cache|null
     */
    protected static $cache;

    /**
     * Database object.
     *
     * @var DataBase|null
     */
    protected static $dataBase;

    /**
     * Translator object.
     *
     * @var Translator|null
     */
    protected static $i18n;

    /**
     * MiniLog object.
     *
     * @var MiniLog|null
     */
    protected static $miniLog;

    /**
     * CronClass constructor.
     */
    public function __construct()
    {
        if (!isset(self::$cache)) {
            self::$cache = new Cache();
        }
        if (!isset(self::$dataBase)) {
            self::$dataBase = new DataBase();
        }
        if (!isset(self::$i18n)) {
            self::$i18n = new Translator();
        }
        if (!isset(self::$miniLog)) {
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Select and execute the relevant controller for the cron.
     *
     * @return mixed
     */
    abstract public function run();
}
