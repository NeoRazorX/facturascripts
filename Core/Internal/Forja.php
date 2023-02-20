<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Internal;

final class Forja
{
    const PLUGIN_LIST_URL = 'https://facturascripts.com/PluginInfoList';

    /** @var array */
    private static $pluginList;

    public static function plugins(): array
    {
        if (!isset(self::$pluginList)) {
            $json = file_get_contents(self::PLUGIN_LIST_URL);
            self::$pluginList = json_decode($json, true);
        }

        return self::$pluginList;
    }
}
