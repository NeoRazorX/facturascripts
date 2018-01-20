<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;

/**
 * This class centralizes the generation of the document code.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class NewCodigoDoc
{

    /**
     * Database object.
     *
     * @var DataBase
     */
    private static $dataBase;

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
        if (!isset(self::$dataBase)) {
            self::$dataBase = new DataBase();
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
        $numero = 1;

        $sql = "SELECT MAX(" . self::$dataBase->sql2Int('numero') . ") as num FROM " . $tableName
            . " WHERE codejercicio = " . self::$dataBase->var2str($codejercicio)
            . " AND codserie = " . self::$dataBase->var2str($codserie) . ";";

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            $numero = 1 + (int) $data[0]['num'];
        }

        return $numero;
    }

    /**
     * Returns the document code
     *
     * @param string $tableName
     * @param int    $numero
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
