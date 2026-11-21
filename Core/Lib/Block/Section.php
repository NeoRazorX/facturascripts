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

/**
 * Agrupa bloques, uno debajo de otro, bajo un título.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Section extends Container
{
    /**
     * @param string $name
     * @param string $title clave de traducción del título
     * @param string $icon
     *
     * @return static
     */
    public static function make(string $name, string $title = '', string $icon = '')
    {
        $section = new static($name);

        return $section->setTitle($title)->setIcon($icon);
    }

    public function template(): string
    {
        return 'Block/Section.html.twig';
    }
}
