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
 * Footer fixed at the bottom of the current page, with a left and a right
 * text, like the one in the core PDF export ("1 / 1" and "Generated at...").
 */
class PageFooterBlock extends AbstractBlock
{
    /** @var string */
    protected $left;

    /** @var string */
    protected $right;

    public function __construct(string $left = '', string $right = '')
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function render(): string
    {
        return '<div class="' . $this->css('page-footer') . '">'
            . '<div>' . $this->escape($this->left) . '</div>'
            . '<div>' . $this->escape($this->right) . '</div>'
            . '</div>';
    }
}
