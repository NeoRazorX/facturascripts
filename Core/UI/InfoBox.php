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

class InfoBox extends Component
{
    /** @var string */
    protected $description = '';

    /** @var string */
    protected $title = '';

    public function description(): string
    {
        return $this->description;
    }

    public function render(string $context = ''): string
    {
        return '<div class="card shadow-sm mb-3">'
            . '<div class="card-body">'
            . '<h5 class="card-title">' . $this->title . '</h5>'
            . '<p class="card-text">' . $this->description . '</p>'
            . '</div>'
            . '</div>';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }
}
