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
 * Define atributos y métodos globales a todas las clases
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José Tovar
 */
abstract class CronClass
{
    /**
     * Objeto de la cache
     *
     * @var Cache|null
     */
    protected static $cache = null;

    /**
     * Objeto de la base de datos
     *
     * @var DataBase|null
     */
    protected static $dataBase = null;

    /**
     * Objeto del traductor
     *
     * @var Translator|null
     */
    protected static $i18n = null;

    /**
     * Objeto minilog
     *
     * @var MiniLog|null
     */
    protected static $miniLog = null;

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
     * Selecciona y ejecuta el controlador pertinente para el cron.
     *
     * @return mixed
     */
    abstract public function run();
}
