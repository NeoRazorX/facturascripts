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

namespace FacturaScripts\Core\Lib\PDF\Dynamic\Blocks;

/**
 * Diagonal text overlay across the current page, like the draft invoice
 * warning of the core PDF export (red rotated text over the content).
 */
class WatermarkTextBlock extends AbstractBlock
{
    /** @var string */
    protected $text;

    /** @var string */
    protected $color;

    public function __construct(string $text, string $color = '#C80000')
    {
        $this->text = $text;
        $this->color = $color;
    }

    public function render(): string
    {
        return '<div class="' . $this->css('watermark-text') . '" style="color: ' . $this->escape($this->color) . ';">'
            . $this->escape($this->text)
            . '</div>';
    }
}
