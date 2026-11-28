<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Template;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;

/**
 * Modelo de prueba sobre la tabla test_model_fields, usado por ModelFieldsTest
 * para comprobar que la lista de campos se recarga cuando cambia la tabla.
 */
class TestModelFields extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $email;

    /** @var string */
    public $email2;

    /** @var int */
    public $id;

    /** @var int */
    public $numero;

    /** Descarta la lista de campos en memoria y en caché, como si fuese una petición nueva. */
    public static function forgetFields(): void
    {
        static::$fields = [];
        Cache::delete('model-fields-TestModelFields');
    }

    public function install(): string
    {
        return '';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'test_model_fields';
    }
}
