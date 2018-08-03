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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DivisaTools;

/**
 * Description of WidgetItemMoney
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemMoney extends WidgetItemNumberBase
{

    /**
     * Tools to work with currencies.
     *
     * @var DivisaTools
     */
    private static $divisaTools;

    /**
     * WidgetItemMoney constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->decimal = FS_NF0;
        $this->type = 'money';

        if (!isset(self::$divisaTools)) {
            self::$divisaTools = new DivisaTools();
        }
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
            return '-';
        }

        $style = $this->getTextOptionsHTML($value);
        return '<span' . $style . '>' . self::$divisaTools->format($value, $this->decimal) . '</span>';
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);

        if (empty($this->decimal)) {
            $widgetAtributes = $column->widget->attributes();
            $this->decimal = isset($widgetAtributes->decimal) ? (int) $widgetAtributes->decimal : FS_NF0;
        }
    }
}
