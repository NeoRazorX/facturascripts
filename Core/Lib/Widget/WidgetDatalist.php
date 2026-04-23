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

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Tools;

/**
 * Description of WidgetDatalist
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class WidgetDatalist extends WidgetSelect
{
    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Dinamic/Assets/JS/WidgetDatalist.js?v=' . Tools::date());
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'datalist', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        if ($this->parent) {
            $class = $class . ' parentDatalist';
        }

        $list = $this->fieldname . '-list-' . $this->getUniqueId();
        $html = '<input type="text" name="' . $this->fieldname . '" value="' . $this->value . '"'
            . ' class="' . $class . '"'
            . ' list="' . $list . '"'
            . $this->inputHtmlExtraParams()
            . ' parent="' . $this->parent . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' data-limit="' . $this->limit . '"'
            . '/>';

        $html .= '<datalist id="' . $list . '">';
        foreach ($this->values as $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $html .= '<option value="' . $title . '" />';
        }
        $html .= '</datalist>';
        return $html;
    }
}
