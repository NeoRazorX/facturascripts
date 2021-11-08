<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Dinamic\Lib\IPFilter;

/**
 * Description of ToolBox
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ToolBox
{

    /**
     * @return AppSettings
     */
    public static function appSettings(): AppSettings
    {
        return new AppSettings();
    }

    /**
     * @return Cache
     */
    public static function cache(): Cache
    {
        return new Cache();
    }

    /**
     * @return DivisaTools
     */
    public static function coins(): DivisaTools
    {
        return new DivisaTools();
    }

    /**
     * @return FileManager
     */
    public static function files(): FileManager
    {
        return new FileManager();
    }

    /**
     * @param string $langcode
     *
     * @return Translator
     */
    public static function i18n(string $langcode = ''): Translator
    {
        return new Translator($langcode);
    }

    /**
     * @param string $channel
     *
     * @return MiniLog
     */
    public static function i18nLog(string $channel = ''): MiniLog
    {
        $translator = new Translator();
        return new MiniLog($channel, $translator);
    }

    /**
     * @return IPFilter
     */
    public static function ipFilter(): IPFilter
    {
        return new IPFilter();
    }

    /**
     * @param string $channel
     *
     * @return MiniLog
     */
    public static function log(string $channel = ''): MiniLog
    {
        return new MiniLog($channel);
    }

    /**
     * @return NumberTools
     */
    public static function numbers(): NumberTools
    {
        return new NumberTools();
    }

    /**
     * @return string
     */
    public static function today(): string
    {
        return date('d-m-Y');
    }

    /**
     * @return Utils
     */
    public static function utils(): Utils
    {
        return new Utils();
    }
}
