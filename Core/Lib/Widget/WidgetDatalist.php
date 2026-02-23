<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\CodeModel;
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
            . ' id="input-' . $list . '"'
            . ' class="' . $class . '"'
            . $this->inputHtmlExtraParams()
            . ' parent="' . $this->parent . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' data-limit="' . $this->limit . '"'
            . '/>';

        $html .= $this->generateAutocompleteScript($list);

        return $html;
    }

    /**
     * Set datasource data and Load data from Model into values array.
     *
     * @param array $child
     * @param bool $loadData
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        $this->source = $child['source'];
        $this->fieldcode = $child['fieldcode'] ?? 'id';
        $this->fieldfilter = $child['fieldfilter'] ?? $this->fieldfilter;
        $this->fieldtitle = $child['fieldtitle'] ?? $this->fieldcode;
        $this->limit = $child['limit'] ?? CodeModel::getlimit();
        if ($loadData && $this->source) {
            static::$codeModel::setLimit($this->limit);
            $values = static::$codeModel->all($this->source, $this->fieldcode, $this->fieldtitle, false);
            $this->setValuesFromCodeModel($values, $this->translate);
        }
    }

    /**
     * Genera el script de autocomplete para el campo input.
     *
     * @param $list
     * @return string
     */
    protected function generateAutocompleteScript($list)
    {
        $options = array_map(function ($option) {
            return empty($option['title']) ? $option['value'] : $option['title'];
        }, $this->values);

        $optionsJson = json_encode($options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return '<script>
            $(document).ready(function() {
                const options = ' . $optionsJson . ';
    
                $("#input-' . htmlspecialchars($list, ENT_QUOTES, 'UTF-8') . '").autocomplete({
                    minLength: 3,               
                    source: function(request, response) {
                        const term = request.term.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                        const matches = options.filter(option => {
                            const normalizedOption = option.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                            return normalizedOption.includes(term);
                        });
                        response(matches);
                    }
                });
            });
        </script>';
    }
}
