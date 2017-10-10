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
namespace FacturaScripts\Core\Lib;

/**
 * Description of NewCodigoDoc
 *
 * @author carlos
 */
class NewCodigoDoc
{
    private static $option;

    public function __construct()
    {
        if (!isset(self::$option)) {
            self::$option = 'new';
        }
    }

    public function getOption()
    {
        return self::$option;
    }

    public function setOption($value)
    {
        self::$option = $value;
    }

    public function options()
    {
        return ['new', 'eneboo'];
    }

    public function getNumero($tableName, $codejercicio, $codserie)
    {
        return mt_rand(1, 99999999);
    }

    public function getCodigo($tableName, $numero, $codserie, $codejercicio)
    {
        /// provisional
        return $codejercicio.$codserie.$numero;
    }
}
