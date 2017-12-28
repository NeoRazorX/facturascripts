<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

/**
 * Relate a customer with a sub-account for each exercise.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class SubcuentaCliente
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Sub account ID.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Customer code.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Sub-account code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_subcuentascli';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the subaccount.
     *
     * @return bool|mixed
     */
    public function getSubcuenta()
    {
        $subc = new Subcuenta();
        $subc->loadFromCode($this->idsubcuenta);
        return $subc;
    }
}
