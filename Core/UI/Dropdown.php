<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI;

class Dropdown extends Button
{
    protected $links = [];

    public function addLink(string $label, string $url, string $icon = ''): self
    {
        $this->links[] = ['icon' => $icon, 'label' => $label, 'url' => $url];

        return $this;
    }

    public function render(): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . ' mr-1"></i> ' : '';
        $label = $this->label ?? $this->name();
        $counter = empty($this->counter) ? '' : '<span class="badge badge-light ml-1">' . $this->counter . '</span> ';

        return '<div class="btn-group">'
            . '<div class="dropdown">'
            . '<button class="btn btn-' . $this->color . ' dropdown-toggle" type="button" data-toggle="dropdown"'
            . ' aria-expanded="false" id="' . $this->id() . '" title="' . $this->description . '">'
            . $icon . $label . $counter
            . '</button>'
            . '<div class="dropdown-menu">' . $this->renderLinks() . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderLinks(): string
    {
        $anchors = [];
        foreach ($this->links as $link) {
            if ($link['label'] === '-') {
                $anchors[] = '<div class="dropdown-divider"></div>';
                continue;
            }

            $icon = $link['icon'] ? '<i class="' . $link['icon'] . '"></i> ' : '';
            $anchors[] = '<a class="dropdown-item" href="' . $link['url'] . '">' . $icon . $link['label'] . '</a>';
        }

        return implode("\n", $anchors);
    }
}
