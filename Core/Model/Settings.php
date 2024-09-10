<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Tools;

/**
 * Description of Settings
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Settings extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Identifier of the group of values.
     *
     * @var string
     */
    public $name;

    /**
     * Set of configuration values
     *
     * @var array
     */
    public $properties;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!is_array($this->properties) || !array_key_exists($name, $this->properties)) {
            return null;
        }

        // si contiene html, lo limpiamos
        if (is_string($this->properties[$name]) && strpos($this->properties[$name], '<') !== false) {
            return Tools::noHtml($this->properties[$name]);
        }

        return $this->properties[$name];
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        Tools::settingsClear();
        return true;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    public function clear()
    {
        parent::clear();
        $this->properties = [];
    }

    /**
     * Load data from array
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        parent::loadFromData($data, ['properties', 'action']);
        $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
    }

    public static function primaryColumn(): string
    {
        return 'name';
    }

    public function save(): bool
    {
        // escapamos el html
        $this->name = Tools::noHtml($this->name);

        if (false === parent::save()) {
            return false;
        }

        Tools::settingsClear();
        return true;
    }

    public static function tableName(): string
    {
        return 'settings';
    }

    public function url(string $type = 'auto', string $list = 'Edit'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        return parent::saveInsert(['properties' => json_encode($this->properties)]);
    }

    protected function saveUpdate(array $values = []): bool
    {
        return parent::saveUpdate(['properties' => json_encode($this->properties)]);
    }
}
