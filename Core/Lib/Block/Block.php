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

use FacturaScripts\Core\Template\BlockController;
use FacturaScripts\Core\Tools;
use InvalidArgumentException;

/**
 * Pieza básica de una página de BlockController. Un bloque tiene un nombre único en toda
 * la página, que sirve para dirigirle acciones, pedirlo por separado y localizarlo desde
 * las extensiones de plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Block
{
    /** @var bool */
    private $deferred = false;

    /** @var string */
    protected $icon = '';

    /** @var bool */
    private $lazy = false;

    /** @var string */
    private $name;

    /** @var string */
    protected $title = '';

    /** @var bool */
    private $withoutRecord = false;

    /**
     * Devuelve la plantilla Twig con la que se pinta el bloque.
     */
    abstract public function template(): string;

    public function __construct(string $name)
    {
        // el nombre viaja en urls, ids de html y selectores de javascript
        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name)) {
            throw new InvalidArgumentException('Invalid block name: ' . $name);
        }

        $this->name = $name;
    }

    /**
     * Recursos que el bloque necesita en la página: ['css' => rutas, 'js' => rutas]. Se añaden
     * al cargar la página aunque el bloque esté aplazado, para que estén listos cuando llegue.
     */
    public function assets(): array
    {
        return [];
    }

    /**
     * @return Block[]
     */
    public function children(): array
    {
        return [];
    }

    /**
     * Indica si este bloque es, o contiene, el bloque indicado.
     */
    public function contains(string $name): bool
    {
        return null !== $this->find($name);
    }

    /**
     * Número de registros del bloque, para mostrarlo junto a su título.
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * Marca el bloque como aplazado: no se cargan sus datos y en su lugar se pinta
     * un hueco que lo pide después por separado.
     */
    public function defer(bool $deferred = true): void
    {
        $this->deferred = $deferred;
    }

    public function find(string $name): ?Block
    {
        return $name === $this->name ? $this : null;
    }

    /**
     * Indica si el usuario puede personalizar las columnas del bloque.
     */
    public function hasOptions(): bool
    {
        return false;
    }

    /**
     * Indica si el bloque pinta su propio botón de opciones. Si no, va junto a su título.
     */
    public function hasOptionsButton(): bool
    {
        return false;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function isDeferred(): bool
    {
        return $this->deferred;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    /**
     * Indica si el bloque se muestra. Por defecto los bloques necesitan que exista
     * el registro principal de la página, salvo que se indique showWithoutRecord().
     */
    public function isVisible(bool $hasRecord): bool
    {
        return $hasRecord || $this->withoutRecord;
    }

    /**
     * Carga los datos del bloque. Solo se llama para los bloques visibles y no aplazados.
     *
     * @param BlockController $controller
     * @param bool $target true si la petición va dirigida a este bloque
     */
    public function load(BlockController $controller, bool $target): void
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return static
     */
    public function setIcon(string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Carga perezosa: los datos del bloque no se cargan con la página, sino cuando
     * el bloque se va a mostrar.
     *
     * @return static
     */
    public function setLazy(bool $lazy = true)
    {
        $this->lazy = $lazy;

        return $this;
    }

    /**
     * @param string $title clave de traducción del título
     *
     * @return static
     */
    public function setTitle(string $title)
    {
        $this->title = '' === $title ? '' : Tools::trans($title);

        return $this;
    }

    /**
     * Permite mostrar el bloque aunque no exista el registro principal de la página.
     *
     * @return static
     */
    public function showWithoutRecord(bool $show = true)
    {
        $this->withoutRecord = $show;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }
}
