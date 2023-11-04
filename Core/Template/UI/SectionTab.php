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

namespace FacturaScripts\Core\Template\UI;

use FacturaScripts\Core\Tools;
use Symfony\Component\HttpFoundation\Request;

abstract class SectionTab extends Component
{
    /** @var string */
    public $counter = 0;

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    abstract public function jsInitFunction(): string;

    abstract public function jsRedrawFunction(): string;

    abstract public function load(Request $request): bool;

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setLabel(string $label, array $params = []): self
    {
        $this->label = Tools::lang()->trans($label, $params);

        return $this;
    }
}