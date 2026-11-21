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

use InvalidArgumentException;

/**
 * Raíz del árbol de bloques de una página: los pinta uno debajo de otro.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page extends Container
{
    public function __construct(string $name = 'page')
    {
        parent::__construct($name);
    }

    /**
     * Comprueba que no hay dos bloques con el mismo nombre, ya que el nombre
     * es lo que identifica a cada bloque en las peticiones.
     *
     * @throws InvalidArgumentException
     */
    public function assertUniqueNames(): void
    {
        $counts = array_count_values($this->names());
        foreach ($counts as $name => $count) {
            if ($count > 1) {
                throw new InvalidArgumentException('Duplicated block name: ' . $name);
            }
        }
    }

    /**
     * Decide qué bloques se aplazan en esta petición. Un bloque perezoso se aplaza
     * salvo que la petición vaya dirigida a él o a alguno de los que contiene.
     *
     * @param string $target nombre del bloque al que va dirigida la petición
     * @param string $fragment nombre del bloque que se pide por separado, si lo hay
     */
    public function plan(string $target, string $fragment = ''): void
    {
        // al pedir un bloque por separado es porque ya se va a mostrar
        $root = '' === $fragment ? $this : $this->find($fragment);
        if (null === $root) {
            return;
        }

        $root->defer(false);
        foreach ($root->children() as $child) {
            $this->planBlock($child, '' === $fragment ? $target : $fragment, $root);
        }
    }

    public function template(): string
    {
        return 'Block/Page.html.twig';
    }

    private function planBlock(Block $block, string $target, Block $parent): void
    {
        $block->defer(false);

        // en unas pestañas perezosas se aplazan todas menos la activa
        $inactiveTab = $parent instanceof Tabs && $parent->isLazy() && $parent->activeName($target) !== $block->name();
        $lazy = $block->isLazy() && false === $block instanceof Tabs;
        if (($inactiveTab || $lazy) && false === $block->contains($target)) {
            $block->defer();
            return;
        }

        foreach ($block->children() as $child) {
            $this->planBlock($child, $target, $block);
        }
    }
}
