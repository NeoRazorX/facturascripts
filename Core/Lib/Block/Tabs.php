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
 * Muestra cada uno de sus bloques en una pestaña. Con setLazy() solo se cargan
 * los datos de la pestaña activa; el resto se piden al abrir cada pestaña.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Tabs extends Container
{
    /**
     * Devuelve el nombre de la pestaña activa: la que contiene el bloque al que
     * va dirigida la petición o, en su defecto, la primera.
     */
    public function activeName(string $target = ''): string
    {
        $first = '';
        foreach ($this->children() as $child) {
            if ('' !== $target && $child->contains($target)) {
                return $child->name();
            }

            if ('' === $first) {
                $first = $child->name();
            }
        }

        return $first;
    }

    /**
     * @return static
     */
    public static function make(string $name)
    {
        return new static($name);
    }

    /**
     * Añade un bloque como pestaña. El título y el icono de la pestaña son los del bloque.
     *
     * @return static
     */
    public function tab(Block $block)
    {
        return $this->add($block);
    }

    public function template(): string
    {
        return 'Block/Tabs.html.twig';
    }
}
