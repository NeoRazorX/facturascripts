<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * This class manage all specific method for a WidgetItem of Checkbox type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemCheckBox extends WidgetItem
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'checkbox';
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

    /**
     * Generates the HTML code to display the data in the List controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $checked = in_array($value, ['t', '1']);
        $icon = $checked ? 'fa-check' : 'fa-minus';
        $style = $this->getTextOptionsHTML($checked);
        return '<i class="fa ' . $icon . '" aria-hidden="true" ' . $style . '></i>';
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
        $specialAttributes = $this->specialAttributes();
        $fieldName = '"' . $this->fieldName . '"';
        $checked = in_array(strtolower($value), ['true', 't', '1']) ? ' checked ' : '';

        $html = $this->getIconHTML()
            . '<input name=' . $fieldName . ' id=' . $fieldName
            . ' class="custom-control-input form-check-input" type="checkbox" value="true" '
            . $specialAttributes . $checked . '>'
            . '<span class="custom-control-indicator"></span>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
