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

class InfoBox extends Component
{
    /** @var string */
    protected $color = '';

    /** @var int */
    protected $counter = 0;

    /** @var string */
    protected $icon = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $title = '';

    public function description(): string
    {
        return $this->description;
    }

    public static function make(string $name): self
    {
        return new static($name);
    }

    public function render(string $context = ''): string
    {
        switch ($this->color) {
            default:
                $color = '';
                break;

            case 'danger':
            case 'info':
            case 'primary':
            case 'secondary':
            case 'success':
            case 'warning':
                $color = ' bg-' . $this->color . ' text-white';
                break;
        }

        $icon = empty($this->icon) ? '' : '<i class="fas fa-' . $this->icon . ' mr-1"></i> ';
        $count = empty($this->counter) ? '' : '<span class="badge badge-pill badge-light ml-2">' . $this->counter . '</span> ';

        return '<div class="card' . $color . ' shadow-sm mb-3">'
            . '<div class="card-body">'
            . '<h5 class="card-title mb-0">' . $icon . $this->title . $count . '</h5>'
            . '<p class="card-text">' . $this->description . '</p>'
            . '</div>'
            . '</div>';
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

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setDescription(string $description, array $params = []): self
    {
        $this->description = Tools::lang()->trans($description, $params);

        return $this;
    }

    public function setTitle(string $title, array $params = []): self
    {
        $this->title = Tools::lang()->trans($title, $params);

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }
}
