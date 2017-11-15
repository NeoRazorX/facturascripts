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

use FacturaScripts\Core\Base\DivisaTools;

/**
 * Description of WidgetItemMoney
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemMoney extends WidgetItem
{
    /**
     * Class that formats currency
     *
     * @var DivisaTools
     */
    private static $divisaTools;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

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
            return '';
        }

        $style = $this->getTextOptionsHTML($value);
        $html = '<span' . $style . '>' . self::$divisaTools->format($value) . '</span>';
        return $html;
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
}
