<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Tools;

/**
 * Description of RowActions
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowActions extends VisualItem
{
    /**
     * @var array
     */
    protected $children;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->children = $data['children'] ?? [];
    }

    /**
     * Añade un nuevo botón. Si el array del botón contiene la clave 'group',
     * el botón se añade como un ítem dentro del dropdown con ese nombre. Si
     * el grupo todavía no existe, se crea automáticamente.
     *
     * @param array $btnArray
     */
    public function addButton(array $btnArray): void
    {
        if (!isset($btnArray['tag'])) {
            $btnArray['tag'] = 'button';
        }

        if (!empty($btnArray['group'])) {
            $groupName = $btnArray['group'];
            foreach ($this->children as $key => $child) {
                if (($child['tag'] ?? '') === 'group' && ($child['name'] ?? '') === $groupName) {
                    $this->children[$key]['children'][] = $btnArray;
                    return;
                }
            }

            // el grupo no existe, se crea y se reintenta
            $this->addButtonGroup(['name' => $groupName, 'label' => $groupName]);
            $this->addButton($btnArray);
            return;
        }

        $this->children[] = $btnArray;
    }

    /**
     * Añade un nuevo grupo de botones (dropdown). Se pueden colocar botones
     * dentro llamando a addButton() con la clave 'group' establecida al
     * nombre de este grupo.
     *
     * @param array $groupArray
     */
    public function addButtonGroup(array $groupArray): void
    {
        $groupArray['tag'] = 'group';
        if (!isset($groupArray['children'])) {
            $groupArray['children'] = [];
        }

        $this->children[] = $groupArray;
    }

    public function render(bool $small = false, string $viewName = ''): string
    {
        $html = '';
        foreach ($this->children as $child) {
            $tag = $child['tag'] ?? '';
            if ($tag === 'button') {
                $button = new RowButton($child);
                $html .= $button->render($small, $viewName);
            } elseif ($tag === 'group') {
                $html .= $this->renderGroup($child, $small, $viewName);
            }
        }

        return $html;
    }

    public function renderTop(): string
    {
        $html = '';
        foreach ($this->children as $child) {
            if (($child['tag'] ?? '') !== 'button') {
                continue;
            }

            $button = new RowButton($child);
            $html .= $button->renderTop();
        }

        return $html;
    }

    /**
     * Renderiza un grupo de botones como un dropdown de Bootstrap que
     * contiene sus botones.
     */
    protected function renderGroup(array $group, bool $small, string $viewName): string
    {
        $children = $group['children'] ?? [];
        if (empty($children)) {
            return '';
        }

        // renderiza cada botón como un ítem del dropdown; omite los vacíos (p.ej. filtrados por nivel)
        $items = '';
        foreach ($children as $child) {
            if (($child['tag'] ?? 'button') !== 'button') {
                continue;
            }
            $button = new RowButton($child);
            $items .= $button->renderDropdownItem($viewName);
        }

        if (empty($items)) {
            return '';
        }

        $icon = empty($group['icon']) ? '' : '<i class="' . $group['icon'] . ' fa-fw"></i> ';
        $label = empty($group['label']) ? '' : Tools::trans($group['label']);
        $title = empty($group['title']) ? $label : Tools::trans($group['title']);
        $divID = empty($group['id']) ? '' : ' id="' . $group['id'] . '"';

        $colorClass = empty($group['color']) ? 'btn-light' : $this->colorToClass($group['color'], 'btn-');
        $cssClass = $small
            ? 'btn dropdown-toggle me-1 ' . $colorClass
            : 'btn btn-sm dropdown-toggle me-1 ' . $colorClass;

        $labelSpan = $label === '' ? '' : '<span class="d-none d-xl-inline-block">' . $label . '</span>';

        return '<div class="btn-group">'
            . '<button type="button"' . $divID . ' class="' . $cssClass . '" data-bs-toggle="dropdown"'
            . ' aria-expanded="false" title="' . $title . '">' . $icon . $labelSpan . '</button>'
            . '<div class="dropdown-menu">' . $items . '</div>'
            . '</div>';
    }
}
