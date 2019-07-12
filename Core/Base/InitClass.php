<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

/**
 * Description of InitClass
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class InitClass
{

    /**
     * Cache object.
     *
     * @var Cache
     */
    protected static $cache;

    /**
     * Database object.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Translator object.
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * MiniLog object.
     *
     * @var MiniLog
     */
    protected static $miniLog;

    /**
     * CronClass constructor.
     */
    public function __construct()
    {
        if (!isset(self::$cache)) {
            self::$cache = new Cache();
            self::$dataBase = new DataBase();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Code to load every time FacturaScripts starts.
     */
    abstract public function init();

    /**
     * Code to load every time the plugin is enabled or updated.
     */
    abstract public function update();
}
