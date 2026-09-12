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
 * Table with optional header row, per-column alignments and alternating row colors.
 */
class TableBlock extends AbstractBlock
{
    /** @var array */
    protected $rows;

    /** @var array */
    protected $titles;

    /** @var array */
    protected $alignments;

    public function __construct(array $rows, array $titles = [], array $alignments = [], string $cssClass = 'table-list')
    {
        $this->rows = $rows;
        $this->titles = $titles;
        $this->alignments = $alignments;
        $this->cssClass = $cssClass;
    }

    public function render(): string
    {
        $html = '<table class="' . $this->css('') . '">';

        if (false === empty($this->titles)) {
            $html .= '<thead><tr>';
            foreach (array_values($this->titles) as $num => $title) {
                $html .= '<th class="' . $this->alignment($num) . '">' . $this->escape((string)$title) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach (array_values((array)$row) as $num => $cell) {
                $html .= '<td class="' . $this->alignment($num) . '">' . $this->escape((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    protected function alignment(int $num): string
    {
        $align = $this->alignments[$num] ?? 'left';
        return 'text-' . (in_array($align, ['center', 'right']) ? $align : 'left');
    }
}
