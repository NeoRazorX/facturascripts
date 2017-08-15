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
namespace FacturaScripts\Core\Base;

/**
 * Define atributos y métodos globales a todas las clases
 *
 * @author Carlos García Gómez
 * @author Rafael San José Tovar
 */
abstract class CronClass
{

    protected static $cache = null;
    protected static $dataBase = null;
    protected static $i18n = null;
    protected static $miniLog = null;

    public function __construct($folder = '')
    {
        if (!isset(self::$cache)) {
            self::$cache = new Base\Cache($folder);
        }
        if (!isset(self::$dataBase)) {
            self::$dataBase = new Base\DataBase();
        }
        if (!isset(self::$i18n)) {
            self::$i18n = new Base\Translator($folder, FS_LANG);
        }
        if (!isset(self::$miniLog)) {
            self::$miniLog = new Base\MiniLog();
        }
    }

    abstract public function run();
}
