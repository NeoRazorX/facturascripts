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
 * This class manage all specific method for a WidgetItem of Number type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemNumber extends WidgetItemNumberBase
{

    /**
     * Class that formats the display and provides tools to manage numeric values
     *
     * @var NumberTools
     */
    private static $numberTools;

    /**
     * WidgetItemNumber constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'number';

        if (!isset(self::$numberTools)) {
            self::$numberTools = new NumberTools();
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
        return '<span' . $style . '>' . self::$numberTools->format($value, $this->decimal) . '</span>';
    }
}
