<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Description of Settings
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Settings extends ModelClass
{
    use ModelTrait;

    /**
     * Identifier of the group of values.
     *
     * @var string
     */
    public $name;

    /**
     * Set of configuration values
     *
     * @var string
     */
    protected $properties;

    public function __get(string $key)
    {
        $properties = $this->getProperties();
        if (array_key_exists($key, $properties)) {
            return $properties[$key];
        }

        return parent::__get($key);
    }

    public function __isset(string $key): bool
    {
        $properties = $this->getProperties();
        if (array_key_exists($key, $properties)) {
            return true;
        }

        return parent::__isset($key);
    }

    public function __unset(string $key): void
    {
        $properties = $this->getProperties();
        if (array_key_exists($key, $properties)) {
            $this->removeProperty($key);
            return;
        }

        parent::__unset($key);
    }

    public function __set(string $key, $value): void
    {
        $this->setProperty($key, $value);
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Tools::settingsClear();
    }

    public function getProperties(): array
    {
        $data = json_decode($this->properties ?? '', true);
        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $property => $value) {
            // si contiene html, lo limpiamos
            if (is_string($value) && strpos($value, '<') !== false) {
                $data[$property] = Tools::noHtml($value);
            }
        }

        return $data;
    }

    public function getProperty(string $key): ?string
    {
        $properties = $this->getProperties();

        return $properties[$key] ?? null;
    }

    public static function primaryColumn(): string
    {
        return 'name';
    }

    public function removeProperty(string $key): self
    {
        $properties = $this->getProperties();
        if (array_key_exists($key, $properties)) {
            unset($properties[$key]);
            $this->setProperties($properties);
        }

        return $this;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function setProperty(string $key, ?string $value): self
    {
        $properties = $this->getProperties();
        $properties[$key] = $value;
        $this->setProperties($properties);

        return $this;
    }

    public static function tableName(): string
    {
        return 'settings';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->name = Tools::noHtml($this->name);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'Edit'): string
    {
        return parent::url($type, $list);
    }
}
