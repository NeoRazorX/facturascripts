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
 * Key/value pairs distributed in two columns, like the parallel table of the
 * core PDF export (PDFCore::insertParallelTable): "key: value" with bold keys,
 * no borders and no shading.
 */
class ParallelTableBlock extends AbstractBlock
{
    /** @var array */
    protected $data;

    public function __construct(array $data, string $cssClass = 'table-parallel')
    {
        $this->data = $data;
        $this->cssClass = $cssClass;
    }

    public function render(): string
    {
        $cells = [];
        foreach ($this->data as $key => $value) {
            $cells[] = '<td><b>' . $this->escape((string)$key) . '</b>: ' . $this->escape((string)$value) . '</td>';
        }

        $html = '<table class="' . $this->css('') . '"><tbody>';
        foreach (array_chunk($cells, 2) as $pair) {
            $html .= '<tr>' . $pair[0] . ($pair[1] ?? '<td></td>') . '</tr>';
        }

        return $html . '</tbody></table>';
    }
}
