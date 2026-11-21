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
 * Bloque que contiene otros bloques y decide cómo se distribuyen.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Container extends Block
{
    /** @var Block[] */
    private $children = [];

    /**
     * Añade un bloque al final.
     *
     * @return static
     */
    public function add(Block $block)
    {
        $this->children[] = $block;

        return $this;
    }

    /**
     * Añade un bloque justo después del bloque indicado, esté en este contenedor
     * o en cualquiera de los que contiene.
     *
     * @return bool false si no existe el bloque indicado
     */
    public function addAfter(string $name, Block $block): bool
    {
        return $this->insert($name, $block, 1);
    }

    /**
     * Añade un bloque justo antes del bloque indicado, esté en este contenedor
     * o en cualquiera de los que contiene.
     *
     * @return bool false si no existe el bloque indicado
     */
    public function addBefore(string $name, Block $block): bool
    {
        return $this->insert($name, $block, 0);
    }

    /**
     * Añade un bloque al final del contenedor indicado.
     *
     * @return bool false si no existe el contenedor indicado
     */
    public function addInto(string $name, Block $block): bool
    {
        $container = $this->find($name);
        if (false === $container instanceof Container) {
            return false;
        }

        $container->add($block);

        return true;
    }

    /**
     * @return Block[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->children as $child) {
            $count += $child->count();
        }

        return $count;
    }

    public function find(string $name): ?Block
    {
        if ($name === $this->name()) {
            return $this;
        }

        foreach ($this->children as $child) {
            $found = $child->find($name);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Un contenedor se muestra si lo hace alguno de sus bloques.
     */
    public function isVisible(bool $hasRecord): bool
    {
        foreach ($this->children as $child) {
            if ($child->isVisible($hasRecord)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Devuelve los nombres de todos los bloques, incluido el propio contenedor.
     *
     * @return string[]
     */
    public function names(): array
    {
        $names = [$this->name()];
        foreach ($this->children as $child) {
            $names = $child instanceof Container ?
                array_merge($names, $child->names()) :
                array_merge($names, [$child->name()]);
        }

        return $names;
    }

    /**
     * Elimina el bloque indicado, esté en este contenedor o en cualquiera de los que contiene.
     *
     * @return bool false si no existe el bloque indicado
     */
    public function remove(string $name): bool
    {
        foreach ($this->children as $key => $child) {
            if ($child->name() === $name) {
                unset($this->children[$key]);
                $this->children = array_values($this->children);
                return true;
            }

            if ($child instanceof Container && $child->remove($name)) {
                return true;
            }
        }

        return false;
    }

    private function insert(string $name, Block $block, int $offset): bool
    {
        foreach ($this->children as $key => $child) {
            if ($child->name() === $name) {
                array_splice($this->children, $key + $offset, 0, [$block]);
                return true;
            }

            if ($child instanceof Container && $child->insert($name, $block, $offset)) {
                return true;
            }
        }

        return false;
    }
}
