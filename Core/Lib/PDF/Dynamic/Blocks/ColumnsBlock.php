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

use FacturaScripts\Core\Lib\PDF\Dynamic\BlockInterface;

/**
 * Row with N columns, each one containing a list of blocks. Optional widths in percentage.
 */
class ColumnsBlock extends AbstractBlock
{
    /** @var BlockInterface[][] */
    protected $columns;

    /** @var array */
    protected $widths;

    public function __construct(array $columnsOfBlocks, array $widths = [])
    {
        $this->columns = $columnsOfBlocks;
        $this->widths = $widths;
    }

    public function render(): string
    {
        $html = '<div class="' . $this->css('columns') . '">';
        foreach (array_values($this->columns) as $num => $blocks) {
            $style = isset($this->widths[$num]) ?
                ' style="flex: 0 0 ' . (float)$this->widths[$num] . '%;"' :
                '';
            $html .= '<div' . $style . '>';
            foreach ($blocks as $block) {
                if ($block instanceof BlockInterface) {
                    $html .= $block->render();
                }
            }
            $html .= '</div>';
        }

        return $html . '</div>';
    }
}
