<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\CodeModel;

/**
 * Description of WidgetSelect
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class WidgetSelect extends BaseWidget
{

    /**
     *
     * @var array
     */
    public $values = [];

    public function __construct($data)
    {
        parent::__construct($data);
        foreach ($data['children'] as $child) {
            if ($child['tag'] !== 'values') {
                continue;
            }

            if (isset($child['source'])) {
                $codeModel = new CodeModel();
                $values = $codeModel->all($child['source'], $child['fieldcode'], $child['fieldtitle'], !$this->required);
                $this->setValuesFromCodeModel($values);
            }
        }
    }

    /**
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must uses the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array $values
     * @param bool  $translate
     */
    public function setValuesFromArray($values, $translate = true)
    {
        $this->values = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $this->values[] = ['title' => $value['title'], 'value' => $value['value']];
                continue;
            }

            $this->values[] = [
                'value' => $value,
                'title' => '',
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * Loads the value list from an array with value and title (description)
     *
     * @param array $rows
     * @param bool $translate
     */
    public function setValuesFromCodeModel(&$rows, $translate = False)
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            $title = $translate ? static::$i18n->trans($codeModel->description) : $codeModel->description;
            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $title
            ];
        }
    }

    /**
     *  Translate the fixed titles, if they exist
     */
    private function applyTranslations()
    {
        foreach ($this->values as $key => $value) {
            if (!empty($value['title'])) {
                $this->values[$key]['title'] = static::$i18n->trans($value['title']);
            }
        }
    }

    /**
     * 
     * @return string
     */
    protected function inputHtml()
    {
        $requiredHtml = $this->required ? ' required=""' : '';
        $html = '<select name="' . $this->fieldname . '" class="form-control"' . $requiredHtml . '>';
        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            $selected = ($option['value'] == $this->value) ? ' selected="selected" ' : '';
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $title . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            if ($option['value'] == $this->value) {
                return $option['title'];
            }
        }

        return $this->value;
    }
}
