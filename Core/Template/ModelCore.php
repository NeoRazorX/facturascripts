<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Template;

/**
 * Base class for managing data models
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class ModelCore
{
    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    abstract public static function modelClassName(): string;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    abstract public static function primaryColumn(): string;

    /**
     * List of fields and his values in the table.
     *
     * @var array
     */
    private $model_properties = [];

    /**
     * Return model view field value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!isset($this->model_properties[$name])) {
            $this->model_properties[$name] = null;
        }

        return $this->model_properties[$name];
    }

    /**
     * Check if exits value to property
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->model_properties);
    }

    /**
     * Set value to model view field
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $this->model_properties[$name] = $value;
    }
}
