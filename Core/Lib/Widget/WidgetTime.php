<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Widget para campos de hora, declarado con type="time" en las vistas XMLView.
 * Genera un input time de HTML5 con los atributos min, max y step indicados en la vista.
 * El paso determina la precisión mostrada: con valores inferiores a sesenta segundos la hora
 * se presenta como H:i:s y en caso contrario como H:i, sin segundos.
 * Cuando el campo es obligatorio y no tiene valor, se rellena a partir del mínimo definido.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class WidgetTime extends BaseWidget
{
    /**
     * Indicates the max value
     *
     * @var string
     */
    protected $max;

    /**
     * Indicates the min value
     *
     * @var string
     */
    protected $min;

    /**
     * Indicates the step value
     * If value is major than 59, then cant edit seconds
     *
     * @var string
     */
    protected $step;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->max = $data['max'] ?? '';
        $this->min = $data['min'] ?? '';
        $this->step = $data['step'] ?? '1';
    }

    /**
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
    protected function inputHtml($type = 'time', $extraClass = '')
    {
        return parent::inputHtml($type, $extraClass);
    }

    /**
     * Add extra attributes to html input field
     *
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $step = ' step="' . $this->step . '"';
        $min = $this->min !== '' ? ' min="' . $this->min . '"' : '';
        $max = $this->max !== '' ? ' max="' . $this->max . '"' : '';
        return $min . $max . $step . parent::inputHtmlExtraParams();
    }

    /**
     *
     * @param object $model
     */
    protected function setValue($model)
    {
        parent::setValue($model);
        if (null === $this->value && $this->required) {
            $this->value = empty($this->min) ? $this->getTimeValue(0) : $this->getTimeValue($this->value);
        }
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : $this->getTimeValue($this->value);
    }

    /**
     *
     * @param string|int $value
     *
     * @return string
     */
    protected function getTimeValue($value)
    {
        $format = $this->step < 60 ? 'H:i:s' : 'H:i';
        return is_numeric($value) ? date($format, $value) : date($format, strtotime($value));
    }
}
