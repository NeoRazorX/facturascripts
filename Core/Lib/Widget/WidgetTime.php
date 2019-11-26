<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of WidgetTime
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
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
        $data['icon'] = $data['icon'] ?? 'fas fa-clock';
        parent::__construct($data);

        $this->max = $data['max'] ?? '';
        $this->min = $data['min'] ?? '';
        $this->step = $data['step'] ?? '1';
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
