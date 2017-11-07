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

use FacturaScripts\Core\Base\NumberTools;

/**
 * Description of WidgetItemNumber
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemNumber extends WidgetItem
{
    /**
     * Clase para formatear las visualizaciones y herramientas
     * para tratar los valores numéricos
     *
     * @var NumberTools
     */
    private static $numberTools;

    /**
     * Numero de decimales para tipos numéricos
     *
     * @var int
     */
    public $decimal;

    /**
     * Valor del incremento/decremento
     *
     * @var string
     */
    public $step;

    /**
     * Valor máximo
     *
     * @var string
     */
    public $max;

    /**
     * Valor mínimo
     *
     * @var string
     */
    public $min;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'number';
        $this->decimal = 0;
        $this->step = 'any';
        $this->max = '';
        $this->min = '';

        if (!isset(self::$numberTools)) {
            self::$numberTools = new NumberTools();
        }
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $column
     * @param \SimpleXMLElement $widgetAtributes
     */
    protected function loadFromXMLColumn($column, $widgetAtributes)
    {
        parent::loadFromXMLColumn($column, $widgetAtributes);

        $this->decimal = (int) $widgetAtributes->decimal;
        $this->step = (string) $widgetAtributes->step;
        $this->min = (string) $widgetAtributes->min;
        $this->max = (string) $widgetAtributes->max;
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param \SimpleXMLElement $column
     */
    protected function loadFromJSONColumn($column)
    {
        parent::loadFromJSONColumn($column);

        $this->decimal = (int) $column['widget']['decimal'];
        $this->step = (string) $column['widget']['step'];
        $this->min = (string) $column['widget']['min'];
        $this->max = (string) $column['widget']['max'];
    }

    /**
     * Genera el código html para atributos especiales de widgets numericos como:
     * step
     * valor mínimo
     * valor máximo
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $base = parent::specialAttributes();
        $step = (empty($this->step)) ? '' : ' step="' . $this->step . '"';
        $min = (empty($this->min)) ? '' : ' min="' . $this->min . '"';
        $max = (empty($this->max)) ? '' : ' max="' . $this->max . '"';
        return $base . $step . $min . $max;
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

        $style = $this->getTextOptionsHTML($value);
        $html = '<span' . $style . '>' . self::$numberTools->format($value, $this->decimal) . '</span>';
        return $html;
    }

    /**
     * Genera el código html para la visualización y edición de los datos
     * en el controlador Edit / EditList
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
}
