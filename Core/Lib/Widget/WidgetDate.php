<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of WidgetDate
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetDate extends BaseWidget
{

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        $data['icon'] = $data['icon'] ?? 'fas fa-calendar-alt';
        parent::__construct($data);
    }

    /**
     *
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname);
        $model->{$this->fieldname} = empty($value) ? null : $value;
    }

    /**
     * 
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = 'datepicker')
    {
        if ($this->readonly()) {
            $extraClass = '';
        }

        return parent::inputHtml($type, $extraClass);
    }

    /**
     * 
     * @return string
     */
    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        if (is_numeric($this->value)) {
            return date('d-m-Y', $this->value);
        }

        return date('d-m-Y', strtotime($this->value));
    }

    /**
     * 
     * @param string $initialClass
     * @param string $alternativeClass
     *
     * @return string
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        $initialClass .= ' text-nowrap';

        /// is today? is the future?
        if (strtotime($this->value) >= strtotime(date('Y-m-d'))) {
            $alternativeClass = 'font-weight-bold';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }
}
