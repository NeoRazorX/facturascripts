<?php

declare(strict_types=1);
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;

class WidgetJson extends WidgetText
{
    protected function recursiveRender($arg, $class = 'col-12'): string
    {
        $html = '';

        $html .= '<style>
            .widgetjsonkeyvalue{
                background-color: #e9ecef;
                padding: .375rem .75rem;
                border: 1px solid #ced4da;
                border-radius: .25rem;
            }
        </style>';

        $html .= '<div class="form-row widgetjsonkeyvalue">';

        foreach ($arg as $key => $value) {
            $html .= '<div class="' . $class . '">';

            if (is_array($value)) {
                $nextArray = $arg[$key];
                $nextClass = count($value) > 15 ? 'col-3' : 'col-6';
                $html .= $key . ': ' . $this->recursiveRender($nextArray, $nextClass);
            } else {
                $html .= $key . ': <strong>' . $value . '</strong>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $data = json_decode(htmlspecialchars_decode($this->value), true);

        return $this->recursiveRender($data);
    }
}
