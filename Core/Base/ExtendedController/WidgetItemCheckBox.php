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
 * Description of WidgetItemCheckBox
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemCheckBox extends WidgetItem
{
    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'checkbox';
    }

    /**
     * Genera el código html para atributos especiales como:
     * hint
     * sólo lectura
     * valor obligatorio
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $readOnly = empty($this->readOnly) ? '' : ' disabled';
        return parent::specialAttributes() . $readOnly;
    }

    /**
     * Genera el código html para la visualización de los datos en el
     * controlador List
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
        $html = '<i class="fa ' . $icon . '" aria-hidden="true"' . $style . '></i>';
        return $html;
    }

    /**
     * Genera el código html para la visualización y edición de los datos
     * en el controlador List / Edit
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
            . ' class="custom-control-input form-check-input" type="checkbox" value="true"'
            . $specialAttributes . $checked . '>'
            . '<span class="custom-control-indicator"></span>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
