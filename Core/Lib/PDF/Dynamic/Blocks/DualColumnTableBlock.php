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
 * Two column key => value table.
 */
class DualColumnTableBlock extends AbstractBlock
{
    /** @var array */
    protected $data;

    public function __construct(array $data, string $cssClass = 'table-dual')
    {
        $this->data = $data;
        $this->cssClass = $cssClass;
    }

    public function render(): string
    {
        $html = '<table class="' . $this->css('') . '"><tbody>';
        foreach ($this->data as $key => $value) {
            $html .= '<tr><td>' . $this->escape((string)$key) . '</td>'
                . '<td>' . $this->escape((string)$value) . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }
}
