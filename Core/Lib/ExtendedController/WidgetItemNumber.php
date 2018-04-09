<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\NumberTools;

/**
 * This class manage all specific method for a WidgetItem of Number type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemNumber extends WidgetItem
{

    /**
     * Number of decimals for numeric types
     *
     * @var int
     */
    public $decimal;

    /**
     * Maximum value
     *
     * @var string
     */
    public $max;

    /**
     * Minimum value
     *
     * @var string
     */
    public $min;

    /**
     * Class that formats the display and provides tools to manage numeric values
     *
     * @var NumberTools
     */
    private static $numberTools;

    /**
     * Increment/decrement value
     *
     * @var string
     */
    public $step;

    /**
     * WidgetItemNumber constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->decimal = 0;
        $this->max = '';
        $this->min = '';
        $this->step = 'any';
        $this->type = 'number';

        if (!isset(self::$numberTools)) {
            self::$numberTools = new NumberTools();
        }
    }

    /**
     * Generates the HTML code to display and edit  the data in the Edit / EditList controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();
        return $this->standardEditHTMLWidget($value, $specialAttributes);
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

        $style = $this->getTextOptionsHTML($value);
        return '<span' . $style . '>' . self::$numberTools->format($value, $this->decimal) . '</span>';
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $widget
     */
    public function loadFromJSON($widget)
    {
        parent::loadFromJSON($widget);

        $this->decimal = (int) $widget['decimal'];
        $this->step = (string) $widget['step'];
        $this->min = (string) $widget['min'];
        $this->max = (string) $widget['max'];
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);

        $widgetAtributes = $column->widget->attributes();
        $this->decimal = (int) $widgetAtributes->decimal;
        $this->step = (string) $widgetAtributes->step;
        $this->min = (string) $widgetAtributes->min;
        $this->max = (string) $widgetAtributes->max;
    }

    /**
     * Generates the HTML code for widget special attributes such as:
     *  - 'step': difference to increase/decrease
     *  - 'max': maximum value
     *  - 'min': minimum value
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $base = parent::specialAttributes();
        $step = empty($this->step) ? ' step="any"' : ' step="' . $this->step . '"';
        $min = empty($this->min) ? '' : ' min="' . $this->min . '"';
        $max = empty($this->max) ? '' : ' max="' . $this->max . '"';

        return $base . $step . $min . $max;
    }
}
