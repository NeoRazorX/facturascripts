<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * Indicate the desired date format
     *
     * @var string
     */
    protected $format;

    /**
     * Indicates the min value
     *
     * @var string
     */
    protected $min;

    /**
     * Indicates the max value
     *
     * @var string
     */
    protected $max;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $data['icon'] = $data['icon'] ?? 'fas fa-calendar-alt';
        parent::__construct($data);
        $this->format = $data['format'] ?? 'd-m-Y';
        $this->min = $data['min'] ?? '';
        $this->max = $data['max'] ?? '';
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
    protected function inputHtml($type = 'date', $extraClass = '')
    {
        $cssFormControl = $this->css('form-control');
        $class = empty($extraClass) ? $cssFormControl : $cssFormControl . ' ' . $extraClass;
        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . date('Y-m-d', strtotime($this->value))
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * Add extra attributes to html input field
     *
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $min = $this->min !== '' ? ' min="' . $this->min . '"' : '';
        $max = $this->max !== '' ? ' max="' . $this->max . '"' : '';
        return $min . $max . parent::inputHtmlExtraParams();
    }

    /**
     *
     * @param object $model
     */
    protected function setValue($model)
    {
        parent::setValue($model);
        if (null === $this->value && $this->required) {
            $this->value = empty($this->min)
                ? $this->getDateValue(date($this->format))
                : $this->getDateValue($this->value);
        }
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : $this->getDateValue($this->value);
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
        /// is today? is the future?
        if (strtotime($this->value) >= strtotime(date('Y-m-d'))) {
            $alternativeClass = 'font-weight-bold';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }

    /**
     *
     * @param string $value
     * @return string
     */
    private function getDateValue($value)
    {
        if (is_numeric($value)) {
            return date($this->format, $value);
        }

        return date($this->format, strtotime($value));
    }
}
