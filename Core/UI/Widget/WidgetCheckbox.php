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

namespace FacturaScripts\Core\UI\Widget;

use FacturaScripts\Core\Template\UI\Widget;
use FacturaScripts\Core\Tools;

class WidgetCheckbox extends Widget
{
    public function render(string $context = ''): string
    {
        switch ($context) {
            default:
                return '<div class="form-check mb-3">'
                    . '<input class="form-check-input" type="checkbox" name="' . $this->field . '" value="' . $this->value
                    . '" id="' . $this->id() . '">'
                    . '<label class="form-check-label" for="' . $this->id() . '">' . $this->label . '</label>'
                    . '</div>';

            case 'td':
                if ($this->value) {
                    return '<td class="text-success text-' . $this->align . '">'
                        . Tools::lang()->trans('yes') . '</td>';
                }
                return is_null($this->value) ?
                    '<td class="text-warning text-' . $this->align . '">-</td>' :
                    '<td class="text-danger text-' . $this->align . '">' . Tools::lang()->trans('no') . '</td>';

            case 'th':
                return '<th class="text-' . $this->align . '">' . $this->label . '</th>';
        }
    }
}