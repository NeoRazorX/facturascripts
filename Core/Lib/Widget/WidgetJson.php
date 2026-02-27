<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;

class WidgetJson extends WidgetTextarea
{
    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addCss($route . '/Dinamic/Assets/CSS/WidgetJson.css?v=' . Tools::date(), 2);
    }

    protected function recursiveRender($arg, $class = 'col-12'): string
    {
        $html = '<div class="form-row widgetjsonkeyvalue">';

        foreach ($arg as $key => $value) {
            $html .= '<div class="' . Tools::noHtml($class) . '">';

            if (is_array($value)) {
                $nextArray = $arg[$key];
                $nextClass = count($value) > 15 ? 'col-3' : 'col-6';
                $html .= Tools::noHtml($key) . ': ' . $this->recursiveRender($nextArray, $nextClass);
            } else {
                $html .= Tools::noHtml($key) . ': <strong>' . Tools::noHtml($value) . '</strong>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function inputHtml($type = 'text', $extraClass = '')
    {
        if (empty($this->value)) {
            return parent::inputHtml($type, $extraClass);
        }

        $data = json_decode(Tools::fixHtml($this->value), true);

        if (!is_array($data)) {
            return parent::inputHtml($type, $extraClass);
        }

        return $this->recursiveRender($data);
    }
}
