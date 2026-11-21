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
 * Coloca los bloques uno al lado del otro. En pantallas pequeñas se apilan.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Columns extends Container
{
    /** @var int[] */
    private $widths = [];

    /**
     * Añade un bloque indicando su ancho, de 1 a 12. Con 0 se reparte el espacio libre.
     *
     * @return static
     */
    public function col(int $width, Block $block)
    {
        $this->widths[$block->name()] = max(0, min(12, $width));

        return $this->add($block);
    }

    /**
     * @return static
     */
    public static function make(string $name)
    {
        return new static($name);
    }

    public function template(): string
    {
        return 'Block/Columns.html.twig';
    }

    /**
     * Devuelve el ancho, de 1 a 12, del bloque indicado, o 0 si se reparte el espacio libre.
     */
    public function width(string $name): int
    {
        return $this->widths[$name] ?? 0;
    }
}
