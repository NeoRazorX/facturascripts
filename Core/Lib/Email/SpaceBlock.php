<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\Template\ExtensionsTrait;

class SpaceBlock extends BaseBlock
{
    use ExtensionsTrait;

    /** @var float */
    protected $height;

    public function __construct(float $height = 30)
    {
        $this->height = $height;
    }

    public function render(bool $footer = false): string
    {
        $this->footer = $footer;
        $return = $this->pipe('render');
        return $return ??
            '<div style="width: 100%; height: ' . $this->height . 'px;"></div>';
    }
}
