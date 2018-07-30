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
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * This class manage all specific method for a WidgetItem of Checkbox type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemCheckBox extends WidgetItem
{

    /**
     * WidgetItemCheckBox constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'checkbox';
    }

    /**
     * Generates the HTML code to display and edit  the data in the List / Edit controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $checked = in_array(strtolower($value), ['true', 't', '1'], false) ? ' checked="" ' : '';
        $html = $this->getIconHTML()
            . '<input name="' . $this->fieldName . '" id="' . $this->fieldName
            . '" class="form-check-input" type="checkbox" value="true" '
            . $this->specialAttributes() . $checked . '/>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Generates the HTML code to display the data in the List controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        $checked = is_bool($value) ? $value : !is_null($value);
        if ($checked) {
            return '<i class="fas fa-check" aria-hidden="true" ' . $this->getTextOptionsHTML($checked) . '></i>';
        }

        return '-';
    }

    /**
     * Generates the HTML code for special attributes like:
     *  - hint
     *  - read only
     *  - mandatory value
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $readOnly = empty($this->readOnly) ? '' : ' disabled';
        return parent::specialAttributes() . $readOnly;
    }
}
