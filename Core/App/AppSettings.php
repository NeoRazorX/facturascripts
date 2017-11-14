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
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Model\Settings;

/**
 * Description of AppSettings
 *
 * @author Carlos García Gómez
 */
class AppSettings
{

    private static $data;

    public function __construct()
    {
        if (!isset(self::$data)) {
            self::$data = [];
        }
    }

    public function get($group, $property, $default = null)
    {
        if (isset(self::$data[$group][$property])) {
            return self::$data[$group][$property];
        }

        return $default;
    }

    public function loadData()
    {
        $settingsModel = new Settings();
        foreach ($settingsModel->all() as $group) {
            self::$data[$group->name] = $group->properties;
        }

        if (!defined('FS_NF0')) {
            define('FS_NF0', $this->get('default', 'decimals', 2));
        }

        if (!defined('FS_NF0_ART')) {
            define('FS_NF0_ART', $this->get('default', 'product_decimals', 2));
        }

        if (!defined('FS_NF1')) {
            define('FS_NF1', $this->get('default', 'decimal_separator', ','));
        }

        if (!defined('FS_NF2')) {
            define('FS_NF2', $this->get('default', 'thousands_separator', ' '));
        }

        if (!defined('FS_POS_DIVISA')) {
            define('FS_POS_DIVISA', $this->get('default', 'divisa_position', 'right'));
        }
    }
}
