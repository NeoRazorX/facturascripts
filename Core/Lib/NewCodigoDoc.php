<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class NewCodigoDoc
{

    /**
     * Code option
     *
     * @var string
     */
    private static $option;

    /**
     * NewCodigoDoc constructor.
     */
    public function __construct()
    {
        if (!isset(self::$option)) {
            self::$option = 'new';
        }
    }

    /**
     * Returns the option
     *
     * @return string
     */
    public function getOption()
    {
        return self::$option;
    }

    /**
     * Assigns the option
     *
     * @param string $value
     */
    public function setOption($value)
    {
        self::$option = $value;
    }

    /**
     * Returns the available options for the code
     *
     * @return string[]
     */
    public function options()
    {
        return ['new', 'eneboo'];
    }

    /**
     * Returns the document number
     *
     * @param string $tableName
     * @param string $codejercicio
     * @param string $codserie
     *
     * @return int
     */
    public function getNumero($tableName, $codejercicio, $codserie)
    {
        return mt_rand(1, 99999999);
    }

    /**
     * Returns the document code
     *
     * @param string $tableName
     * @param int $numero
     * @param string $codserie
     * @param string $codejercicio
     *
     * @return string
     */
    public function getCodigo($tableName, $numero, $codserie, $codejercicio)
    {
        /// Temporary - provisional
        return $codejercicio . $codserie . $numero;
    }
}
