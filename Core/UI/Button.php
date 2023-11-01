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

class Button extends Component
{
    /** @var string */
    public $color = 'secondary';

    /** @var int */
    public $counter = 0;

    /** @var string */
    public $description;

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    public function render(string $context = ''): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . ' mr-1"></i> ' : '';
        $label = $this->label ?? $this->name();
        $counter = empty($this->counter) ? '' : '<span class="badge badge-light ml-1">' . $this->counter . '</span> ';

        return '<button type="button" class="btn btn-' . $this->color . ' mr-1" id="'
            . $this->id() . '" title="' . $this->description . '">'
            . $icon . $label . $counter
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