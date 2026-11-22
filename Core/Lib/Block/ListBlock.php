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

use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Template\BlockController;

/**
 * Listado de registros, definido por su XMLView, con búsqueda, filtros, orden y paginación.
 * Para añadir filtros o colores usa view(), que devuelve el ListView.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListBlock extends ViewBlock
{
    /**
     * @return static
     */
    public function addOrderBy(array $fields, string $label, int $default = 0)
    {
        $this->view()->addOrderBy($fields, $label, $default);

        return $this;
    }

    /**
     * @return static
     */
    public function addSearchFields(array $fields)
    {
        $this->view()->addSearchFields($fields);

        return $this;
    }

    public function countable(): bool
    {
        return true;
    }

    public function countOnly(BlockController $controller): ?int
    {
        return $this->model()->count($this->baseWhere($controller));
    }

    /**
     * El botón de opciones va en la barra del listado, junto al orden.
     */
    public function hasOptionsButton(): bool
    {
        return true;
    }

    public function load(BlockController $controller, bool $target): void
    {
        parent::load($controller, $target);

        // la plantilla del listado pinta el botón de opciones si recibe su url
        if ($this->hasOptions()) {
            $url = 'EditPageOption?code=' . $this->name() . '&url=' . rawurlencode($controller->pageUrl());
            $this->view()->setSettings('optionsUrl', $url);
        }
    }

    /**
     * @param string $name nombre del bloque y del XMLView
     * @param string $modelName
     * @param string $title clave de traducción del título
     * @param string $icon
     *
     * @return static
     */
    public static function make(string $name, string $modelName, string $title = '', string $icon = 'fa-solid fa-list')
    {
        return new static($name, new ListView($name, $title, static::modelClass($modelName), $icon));
    }

    public function view(): ListView
    {
        /** @var ListView $view */
        $view = $this->view;

        return $view;
    }
}
