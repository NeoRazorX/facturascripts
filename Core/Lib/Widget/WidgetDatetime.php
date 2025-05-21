<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Request;

/**
 * Description of WidgetDatetime
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WidgetDatetime extends BaseWidget
{
    protected function inputHtml($type = 'datetime-local', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        $value = empty($this->value) ? '' : date('Y-m-d H:i:s', strtotime($this->value));
        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $value
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname);
        $model->{$this->fieldname} = empty($value) ? null : $value;
    }

    /**
     * @return string
     */
    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        if (is_numeric($this->value)) {
            return date('d-m-Y H:i:s', $this->value);
        }

        return date('d-m-Y H:i:s', strtotime($this->value));
    }

    /**
     * @param string $initialClass
     * @param string $alternativeClass
     *
     * @return string
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        $initialClass .= ' text-nowrap';

        // is today? is the future?
        if ($this->value && strtotime($this->value) >= strtotime(date('Y-m-d'))) {
            $alternativeClass = 'fw-bold';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }
}
