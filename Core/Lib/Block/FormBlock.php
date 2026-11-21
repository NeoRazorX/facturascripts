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

namespace FacturaScripts\Core\Lib\Block;

use FacturaScripts\Core\Lib\ExtendedController\EditView;

/**
 * Formulario para editar un registro, definido por su XMLView.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormBlock extends ViewBlock
{
    /**
     * @param string $name nombre del bloque y del XMLView
     * @param string $modelName
     * @param string $title clave de traducción del título
     * @param string $icon
     *
     * @return static
     */
    public static function make(string $name, string $modelName, string $title = '', string $icon = 'fa-solid fa-edit')
    {
        return new static($name, new EditView($name, $title, static::modelClass($modelName), $icon));
    }
}
