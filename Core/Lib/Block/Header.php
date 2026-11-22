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
use FacturaScripts\Core\Tools;
use InvalidArgumentException;

/**
 * Cabecera de una página de BlockController. No depende de ningún bloque: el controlador
 * declara aquí los botones, desplegables, el estado, los avisos y las estadísticas del registro.
 *
 * Los botones propios de la página (volver, listado, recargar, nuevo, imprimir, duplicar,
 * copiar enlace y el índice de secciones) se muestran u ocultan con show() y hide().
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Header
{
    const BUILT_IN = ['back', 'clone', 'link', 'list', 'new', 'print', 'reload', 'sections'];

    /** @var array */
    private $alerts = [];

    /** @var Closure|null */
    private $breadcrumb;

    /** @var string */
    private $breadcrumbList = '';

    /** @var HeaderButton[] */
    private $buttons = [];

    /** @var string[] */
    private $hidden = ['clone'];

    /** @var array */
    private $stats = [];

    /** @var Closure|null */
    private $status;

    /** @var bool */
    private $sticky = true;

    /**
     * Añade un aviso que se muestra bajo la cabecera mientras se cumpla su condición.
     *
     * @param string $name
     * @param Closure $callback function (?ModelClass $record): ?string, que devuelve el texto
     * del aviso, ya traducido, o null si no hay que mostrarlo
     * @param string $color
     */
    public function alert(string $name, Closure $callback, string $color = 'warning'): self
    {
        $this->alerts[$name] = ['callback' => $callback, 'color' => $color];

        return $this;
    }

    /**
     * Devuelve los avisos que hay que mostrar.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     *
     * @return array lista de arrays con las claves name, color y message
     */
    public function alerts($record): array
    {
        $alerts = [];
        foreach ($this->alerts as $name => $alert) {
            $message = call_user_func($alert['callback'], $record);
            if (false === empty($message)) {
                $alerts[] = ['name' => $name, 'color' => $alert['color'], 'message' => (string)$message];
            }
        }

        return $alerts;
    }

    /**
     * Declara la miga de pan de la página, que sustituye al botón del listado: empieza por
     * el listado, sigue con los registros de los que cuelga el registro y termina en él.
     *
     * @param string $list clave de traducción del listado, el primer elemento de la miga
     * @param Closure $parents function (ModelClass $record): array, que devuelve los registros
     * padre, del más lejano al más cercano, como arrays con las claves title y url
     */
    public function breadcrumb(string $list, Closure $parents): self
    {
        $this->breadcrumb = $parents;
        $this->breadcrumbList = $list;

        return $this;
    }

    /**
     * Devuelve los elementos de la miga de pan, o un array vacío si la página no la declara.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     *
     * @return array lista de arrays con las claves title y url; el último, el propio
     * registro, no lleva url
     */
    public function breadcrumbFor($record): array
    {
        if (null === $this->breadcrumb || null === $record) {
            return [];
        }

        $crumbs = [['title' => Tools::trans($this->breadcrumbList), 'url' => (string)$record->url('list')]];
        $parents = call_user_func($this->breadcrumb, $record);
        foreach (is_array($parents) ? $parents : [] as $parent) {
            if (is_array($parent) && false === empty($parent['title'])) {
                $crumbs[] = ['title' => (string)$parent['title'], 'url' => (string)($parent['url'] ?? '')];
            }
        }

        $crumbs[] = [
            'title' => $record->exists() ? (string)$record->primaryDescription() : Tools::trans('new'),
            'url' => '',
        ];

        return $crumbs;
    }

    /**
     * Añade un botón. Con la clave dropdown va dentro de ese desplegable, y con before o
     * after se coloca antes o después del botón indicado en lugar de al final.
     *
     * @param string $name
     * @param array $config ver HeaderButton
     */
    public function button(string $name, array $config = []): self
    {
        return $this->addButton(new HeaderButton($name, $config), $config);
    }

    /**
     * Devuelve los botones y desplegables que se muestran.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     *
     * @return HeaderButton[]
     */
    public function buttons($record): array
    {
        return array_values(array_filter($this->buttons, fn(HeaderButton $item) => $item->isVisible($record)));
    }

    /**
     * Añade un desplegable, al que se añaden botones con la clave dropdown.
     *
     * @param string $name
     * @param array $config ver HeaderButton
     */
    public function dropdown(string $name, array $config = []): self
    {
        return $this->addButton(new HeaderButton($name, $config, true), $config);
    }

    /**
     * Devuelve el botón o desplegable indicado, esté suelto o dentro de un desplegable.
     */
    public function find(string $name): ?HeaderButton
    {
        foreach ($this->buttons as $button) {
            $found = $button->find($name);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Devuelve el botón que envía la acción indicada, esté suelto o dentro de un desplegable.
     */
    public function findAction(string $action): ?HeaderButton
    {
        foreach ($this->buttons as $button) {
            foreach (array_merge([$button], $button->items(null, false)) as $item) {
                if (false === $item->isDropdown() && 'action' === $item->type && $item->action === $action) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Oculta botones propios de la página: back, clone, link, list, new, print, reload o sections.
     */
    public function hide(string ...$names): self
    {
        $this->hidden = array_values(array_unique(array_merge($this->hidden, $names)));

        return $this;
    }

    public function isSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * Elimina un botón o desplegable, esté suelto o dentro de un desplegable.
     */
    public function remove(string $name): bool
    {
        foreach ($this->buttons as $key => $button) {
            if ($button->name() === $name) {
                unset($this->buttons[$key]);
                $this->buttons = array_values($this->buttons);
                return true;
            }

            if ($button->remove($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Muestra botones propios de la página: back, clone, link, list, new, print, reload o sections.
     */
    public function show(string ...$names): self
    {
        $this->hidden = array_values(array_diff($this->hidden, $names));

        return $this;
    }

    /**
     * Indica si se muestra un botón propio de la página.
     */
    public function shows(string $name): bool
    {
        return in_array($name, self::BUILT_IN, true) && false === in_array($name, $this->hidden, true);
    }

    /**
     * Añade una estadística del registro: un botón con una cifra que lleva a un bloque o a una url.
     *
     * @param string $name
     * @param array $config admite las claves:
     *  - label: clave de traducción; por defecto, el título del bloque
     *  - icon: por defecto, el icono del bloque
     *  - block: bloque al que lleva y del que, si no hay value, se toma la cifra
     *  - url: url a la que lleva, si no lleva a un bloque
     *  - value: function (?ModelClass $record): mixed, que devuelve la cifra ya formateada
     */
    public function stat(string $name, array $config): self
    {
        $this->stats[$name] = $config;

        return $this;
    }

    /**
     * Devuelve las estadísticas que hay que mostrar. Se omiten las que no tienen cifra, como
     * las de un bloque que todavía no ha cargado sus datos.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     * @param Page $page
     *
     * @return array lista de arrays con las claves name, label, icon, block, url y value
     */
    public function stats($record, Page $page): array
    {
        $stats = [];
        foreach ($this->stats as $name => $config) {
            $block = isset($config['block']) ? $page->find((string)$config['block']) : null;
            if (isset($config['value'])) {
                $value = call_user_func($config['value'], $record);
            } else {
                $value = null === $block || $block->isDeferred() ? null : $block->count();
            }
            if (null === $value) {
                continue;
            }

            $stats[] = [
                'name' => $name,
                'label' => $config['label'] ?? '',
                'title' => null === $block ? '' : $block->title(),
                'icon' => $config['icon'] ?? (null === $block ? '' : $block->icon()),
                'block' => null === $block ? '' : $block->name(),
                'url' => (string)($config['url'] ?? ''),
                'value' => $value,
            ];
        }

        return $stats;
    }

    /**
     * Indica cómo obtener el estado del registro, que se muestra junto al título.
     *
     * @param Closure $callback function (?ModelClass $record): ?array, que devuelve un array
     * con el texto, ya traducido, y el color (success, danger, warning...), o null si no hay estado
     */
    public function status(Closure $callback): self
    {
        $this->status = $callback;

        return $this;
    }

    /**
     * Devuelve el estado del registro: un array con las claves label y color, o null si no tiene.
     *
     * @param mixed $record registro principal de la página, o null si no tiene
     */
    public function statusFor($record): ?array
    {
        $status = null === $this->status ? null : call_user_func($this->status, $record);
        if (false === is_array($status) || empty($status[0])) {
            return null;
        }

        return ['label' => (string)$status[0], 'color' => (string)($status[1] ?? 'secondary')];
    }

    /**
     * Indica si la cabecera se queda fija arriba al desplazarse por la página.
     */
    public function sticky(bool $sticky = true): self
    {
        $this->sticky = $sticky;

        return $this;
    }

    private function addButton(HeaderButton $button, array $config): self
    {
        if (in_array($button->name(), self::BUILT_IN, true) || null !== $this->find($button->name())) {
            throw new InvalidArgumentException('Duplicated header button name: ' . $button->name());
        }

        $before = (string)($config['before'] ?? '');
        $after = (string)($config['after'] ?? '');
        if (empty($config['dropdown'])) {
            $this->buttons = HeaderButton::insert($this->buttons, $button, $before, $after);
            return $this;
        }

        $dropdown = $this->find((string)$config['dropdown']);
        if (null === $dropdown || false === $dropdown->isDropdown() || $button->isDropdown()) {
            throw new InvalidArgumentException('Header dropdown not found: ' . $config['dropdown']);
        }

        $dropdown->add($button, $before, $after);
        return $this;
    }
}
