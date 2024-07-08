<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Tools;

/**
 * AppSettings manage the essential data settings of the app.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @deprecated since version 2023.02. Use Tools::settings() instead.
 */
final class AppSettings
{
    /**
     * Return the value of property in group.
     *
     * @param string $group
     * @param string $property
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get(string $group, string $property, $default = null)
    {
        return Tools::settings($group, $property, $default);
    }

    /**
     * Set the value for group property.
     *
     * @param string $group
     * @param string $property
     * @param string $value
     */
    public function set(string $group, string $property, $value)
    {
        Tools::settingsSet($group, $property, $value);
    }

    /**
     * Load default App Settings.
     */
    public function load()
    {
    }

    /**
     * Reloads settings from database.
     */
    public static function reload()
    {
    }

    /**
     * Store the model data in the database.
     */
    public function save()
    {
        Tools::settingsSave();
    }
}
