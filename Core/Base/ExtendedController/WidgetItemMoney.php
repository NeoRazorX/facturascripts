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
     * Clase para formatear monedas
     *
     * @var DivisaTools
     */
    private static $divisaTools;

    /**
     * Numero de decimales para las divisas
     *
     * @var int
     */
    public $decimal;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'money';
        $this->decimal = 2;

        if (!isset(self::$divisaTools)) {
            self::$divisaTools = new DivisaTools();
        }
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
        $aux = empty($this->decimal) ? self::$divisaTools->format($value) : self::$divisaTools->format($value, $this->decimal);
        $html = '<span' . $style . '>' . $aux . '</span>';
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
