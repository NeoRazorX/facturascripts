<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Settings;

/**
 * AppSettings manage the essential data settings of the app.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class AppSettings
{

    /**
     * Array of data settings.
     *
     * @var array
     */
    private static $data = [];

    /**
     * Contains if need to save data.
     *
     * @var bool
     */
    private static $save = false;

    /**
     * Return the value of property in group.
     *
     * @param string $group
     * @param string $property
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($group, $property, $default = null)
    {
        if (!isset(self::$data[$group][$property])) {
            self::$data[$group][$property] = $default;
            self::$save = true;
        }

        return self::$data[$group][$property];
    }

    /**
     * Set the value for group property.
     *
     * @param string $group
     * @param string $property
     * @param string $value
     */
    public function set($group, $property, $value)
    {
        if (!isset(self::$data[$group])) {
            self::$data[$group] = [];
        }

        self::$data[$group][$property] = $value;
    }

    /**
     * Load default App Settings.
     */
    public function load()
    {
        $this->reload();

        /// Constants
        $constants = [
            'FS_CODPAIS' => ['property' => 'codpais', 'default' => 'ESP'],
            'FS_NF0' => ['property' => 'decimals', 'default' => 2],
            'FS_NF1' => ['property' => 'decimal_separator', 'default' => ','],
            'FS_NF2' => ['property' => 'thousands_separator', 'default' => ' '],
            'FS_CURRENCY_POS' => ['property' => 'currency_position', 'default' => 'right'],
            'FS_ITEM_LIMIT' => ['property' => 'item_limit', 'default' => 50],
        ];
        $this->setConstants($constants);

        /// Other default values
        static::get('default', 'coddivisa', 'EUR');
        static::get('default', 'homepage', 'Wizard');
        static::get('default', 'updatesupplierprices', true);
        static::get('default', 'ventasinstock', false);

        if (self::$save) {
            $this->save();
        }
    }

    /**
     * Reloads settings from database.
     */
    public static function reload()
    {
        $settingsModel = new Settings();
        foreach ($settingsModel->all() as $group) {
            self::$data[$group->name] = $group->properties;
        }
    }

    /**
     * Store the model data in the database.
     */
    public function save()
    {
        foreach (self::$data as $key => $value) {
            $settings = new Settings();
            $settings->name = (string) $key;
            $settings->properties = $value;
            $settings->save();
        }

        self::$save = false;
    }

    /**
     * Set the values for constants.
     *
     * @param array $data
     */
    private function setConstants($data)
    {
        foreach ($data as $key => $value) {
            if (!defined($key)) {
                define($key, static::get('default', $value['property'], $value['default']));
            }
        }
    }
}
