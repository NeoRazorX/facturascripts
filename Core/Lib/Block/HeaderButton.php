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

use Closure;
use InvalidArgumentException;

/**
 * Botón o desplegable de la cabecera de una página de BlockController.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class HeaderButton
{
    /** @var string */
    public $action;

    /** @var string */
    public $block;

    /** @var string */
    public $color;

    /** @var bool */
    public $confirm;

    /** @var bool */
    private $dropdown;

    /** @var Closure|null */
    private $enabled;

    /** @var string */
    public $icon;

    /** @var HeaderButton[] */
    private $items = [];

    /** @var string */
    public $js;

    /** @var string */
    public $label;

    /** @var string */
    private $name;

    /** @var bool */
    public $primary;

    /** @var Closure|null */
    private $run;

    /** @var string */
    public $target;

    /** @var string */
    public $title;

    /** @var string */
    public $type;

    /** @var string|Closure */
    private $url;

    /** @var Closure|null */
    private $visible;

    /**
     * @param string $name
     * @param array $config admite las claves:
     *  - label, title: claves de traducción del texto y de la ayuda del botón
     *  - icon, color: icono y color (success, danger, warning, info...)
     *  - type: action (envía una acción, por defecto), link (abre url) o js (ejecuta js)
     *  - action: nombre de la acción que se envía; por defecto, el nombre del botón
     *  - block: bloque al que va dirigida la acción
     *  - run: function (BlockController $controller): void, que ejecuta la acción si no la atiende un bloque
     *  - url, target: destino de los botones de tipo link; url puede ser una
     *    function (?ModelClass $record): string, para que dependa del registro
     *  - js: código de los botones de tipo js
     *  - confirm: true para pedir confirmación antes de enviar la acción
     *  - primary: true para que el botón no se pliegue en el menú "más" en pantallas pequeñas
     *  - visible, enabled: function (?ModelClass $record): bool
     * @param bool $dropdown true si es un desplegable que agrupa otros botones
     */
    public function __construct(string $name, array $config = [], bool $dropdown = false)
    {
        // el nombre viaja en ids de html y selectores de javascript
        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name)) {
            throw new InvalidArgumentException('Invalid header button name: ' . $name);
        }

        $this->name = $name;
        $this->dropdown = $dropdown;
        $this->action = (string)($config['action'] ?? $name);
        $this->block = (string)($config['block'] ?? '');
        $this->color = (string)($config['color'] ?? 'secondary');
        $this->confirm = (bool)($config['confirm'] ?? false);
        $this->enabled = $config['enabled'] ?? null;
        $this->icon = (string)($config['icon'] ?? '');
        $this->js = (string)($config['js'] ?? '');
        $this->label = (string)($config['label'] ?? '');
        $this->primary = (bool)($config['primary'] ?? false);
        $this->run = $config['run'] ?? null;
        $this->target = (string)($config['target'] ?? '');
        $this->title = (string)($config['title'] ?? '');
        $this->type = (string)($config['type'] ?? 'action');
        $this->url = $config['url'] ?? '';
        $this->visible = $config['visible'] ?? null;
    }

    public function add(HeaderButton $button, string $before = '', string $after = ''): void
    {
        $this->items = self::insert($this->items, $button, $before, $after);
    }

    /**
     * Indica cuándo se puede pulsar el botón.
     *
     * @param Closure $callback function (?ModelClass $record): bool
     */
    public function enabledIf(Closure $callback): self
    {
        $this->enabled = $callback;

        return $this;
    }

    public function find(string $name): ?HeaderButton
    {
        if ($name === $this->name) {
            return $this;
        }

        foreach ($this->items as $item) {
            if ($item->name() === $name) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Devuelve el destino de un botón de tipo link.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     */
    public function href($record): string
    {
        return (string)($this->url instanceof Closure ? call_user_func($this->url, $record) : $this->url);
    }

    /**
     * Coloca un botón en una lista, antes o después del indicado o, si no existe, al final.
     *
     * @param HeaderButton[] $list
     *
     * @return HeaderButton[]
     */
    public static function insert(array $list, HeaderButton $button, string $before = '', string $after = ''): array
    {
        foreach ($list as $key => $item) {
            if ($item->name() === $before || $item->name() === $after) {
                array_splice($list, $item->name() === $before ? $key : $key + 1, 0, [$button]);
                return $list;
            }
        }

        $list[] = $button;
        return $list;
    }

    public function isDropdown(): bool
    {
        return $this->dropdown;
    }

    /**
     * @param mixed $record registro principal de la página, o null si no tiene
     */
    public function isEnabled($record): bool
    {
        return null === $this->enabled || (bool)call_user_func($this->enabled, $record);
    }

    /**
     * Un desplegable solo se muestra si lo hace alguno de sus botones.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     */
    public function isVisible($record): bool
    {
        if (null !== $this->visible && false === (bool)call_user_func($this->visible, $record)) {
            return false;
        }

        return false === $this->dropdown || false === empty($this->items($record));
    }

    /**
     * Devuelve los botones del desplegable que se muestran.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     * @param bool $onlyVisible false para devolverlos todos, se muestren o no
     *
     * @return HeaderButton[]
     */
    public function items($record, bool $onlyVisible = true): array
    {
        if (false === $onlyVisible) {
            return $this->items;
        }

        return array_values(array_filter($this->items, fn(HeaderButton $item) => $item->isVisible($record)));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function remove(string $name): bool
    {
        foreach ($this->items as $key => $item) {
            if ($item->name() === $name) {
                unset($this->items[$key]);
                $this->items = array_values($this->items);
                return true;
            }
        }

        return false;
    }

    public function runCallback(): ?Closure
    {
        return $this->run;
    }

    /**
     * Indica cuándo se muestra el botón.
     *
     * @param Closure $callback function (?ModelClass $record): bool
     */
    public function visibleIf(Closure $callback): self
    {
        $this->visible = $callback;

        return $this;
    }
}
