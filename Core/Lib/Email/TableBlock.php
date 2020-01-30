<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of TableBlock
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TableBlock extends BaseBlock
{

    /**
     *
     * @var array
     */
    protected $header;

    /**
     *
     * @var array
     */
    protected $rows;

    /**
     * 
     * @param array $header
     * @param array $rows
     */
    public function __construct(array $header, array $rows)
    {
        $this->header = $header;
        $this->rows = $rows;
    }

    /**
     * 
     * @return string
     */
    public function render(): string
    {
        return '<table>'
            . '<thead>'
            . '<tr>'
            . $this->renderHeaders()
            . '</tr>'
            . '</thead>'
            . '<tbody>'
            . $this->renderRows()
            . '</tbody>'
            . '</table>';
    }

    /**
     * 
     * @return string
     */
    protected function renderHeaders(): string
    {
        $html = '';
        foreach ($this->header as $head) {
            $html .= '<th>' . $head . '</th>';
        }

        return $html;
    }

    /**
     * 
     * @return string
     */
    protected function renderRows(): string
    {
        $html = '';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach ($row as $col) {
                $html .= '<td>' . $col . '</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }
}
