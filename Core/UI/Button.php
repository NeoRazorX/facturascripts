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

use FacturaScripts\Core\Template\UI\Component;
use FacturaScripts\Core\Tools;

class Button extends Component
{
    /** @var string */
    protected $color = 'secondary';

    /** @var int */
    protected $counter = 0;

    /** @var string */
    protected $description;

    /** @var string */
    protected $icon;

    /** @var string */
    protected $label;

    /** @var string */
    protected $modal_id;

    public function label(bool $translate = false): string
    {
        return $translate && !empty($this->label) ?
            Tools::lang()->trans($this->label) :
            $this->label ?? '';
    }

    public function linkModal(Modal $modal): self
    {
        $this->modal_id = $modal->id();

        return $this;
    }

    public function render(string $context = ''): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . ' mr-1"></i> ' : '';
        $counter = empty($this->counter) ? '' : '<span class="badge badge-light ml-1">' . $this->counter . '</span> ';

        $attributes = $this->modal_id ? 'data-toggle="modal" data-target="#' . $this->modal_id . '"' : '';

        return '<button type="button" class="btn btn-' . $this->color . ' mr-1" id="'
            . $this->id() . '" title="' . $this->description . '"' . $attributes . '>'
            . $icon . $this->label(true) . $counter
            . '</button>';
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function setCounter(int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}