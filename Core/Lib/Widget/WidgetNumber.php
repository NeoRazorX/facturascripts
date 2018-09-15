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

use FacturaScripts\Core\Base\NumberTools;

/**
 * Description of WidgetNumber
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class WidgetNumber extends BaseWidget
{

    /**
     *
     * @var NumberTools
     */
    protected static $numberTools;

    /**
     * Indicates the min value
     *
     * @var int
     */
    protected $min;

    /**
     * Indicates the max value
     *
     * @var int
     */
    protected $max;

    /**
     * Indicates the step value
     *
     * @var int
     */
    protected $step;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        if (!isset(static::$numberTools)) {
            static::$numberTools = new NumberTools();
        }

        parent::__construct($data);
        $this->min = $data['min'] ?? 0;
        $this->max = $data['max'] ?? 0;
        $this->step = $data['step'] ?? 0;
    }

    /**
     *
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'number', $extraClass = '')
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
        $min = ($this->min > 0) ? ' min="' . $this->min . '"' : '';
        $max = ($this->max > 0) ? ' max="' . $this->max . '"' : '';
        $step = ($this->step > 0) ? ' step="' . $this->step . '"' : '';
        return $min . $max . $step . parent::inputHtmlExtraParams();
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : static::$numberTools->format($this->value);
    }

    /**
     *
     * @param string $initialClass
     * @param string $alternativeClass
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        if (0 == $this->value) {
            $alternativeClass = 'text-warning';
        } elseif ($this->value < 0) {
            $alternativeClass = 'text-danger';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }
}
