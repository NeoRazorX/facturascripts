<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of TableBlock
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class TableBlock extends BaseBlock
{
    use ExtensionsTrait;

    /** @var array */
    protected $header;

    /** @var array */
    protected $rows;

    public function __construct(array $header, array $rows, string $css = '', string $style = '')
    {
        $this->css = $css;
        $this->style = $style;
        $this->header = $header;
        $this->rows = $rows;
    }

    public function render(bool $footer = false): string
    {
        $this->footer = $footer;
        $return = $this->pipe('render');
        return $return ??
            '<table class="' . (empty($this->css) ? 'table mb-15 w-100' : $this->css) . '">'
            . '<thead>'
            . '<tr>' . $this->renderHeaders() . '</tr>'
            . '</thead>'
            . '<tbody>' . $this->renderRows() . '</tbody>'
            . '</table>';
    }

    protected function renderHeaders(): string
    {
        $return = $this->pipe('renderHeaders');
        if ($return) {
            return $return;
        }

        $html = '';
        foreach ($this->header as $head) {
            $html .= '<th>' . $head . '</th>';
        }

        return $html;
    }

    protected function renderRows(): string
    {
        $return = $this->pipe('renderRows');
        if ($return) {
            return $return;
        }

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
