<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Relate a supplier with a sub-account for each exercise.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class SubcuentaProveedor
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * ID of the sub-account.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Supplier code.
     *
     * @var string
     */
    public $codproveedor;

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
        return 'co_subcuentasprov';
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

    public function install()
    {
        new Subcuenta();

        return '';
    }

    /**
     * Returns the subaccount.
     *
     * @return Subcuenta|bool
     */
    public function getSubcuenta()
    {
        $subcuentaModel = new Subcuenta();

        return $subcuentaModel->get($this->idsubcuenta);
    }

    public function test()
    {
        $subcuentaModel = new Subcuenta();
        if ($subcuentaModel->loadFromCode($this->idsubcuenta)) {
            if ($subcuentaModel->codejercicio === $this->codejercicio) {
                return true;
            }
        }

        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta),
        ];
        if ($subcuentaModel->loadFromCode(null, $where)) {
            if ($subcuentaModel->codejercicio === $this->codejercicio) {
                $this->idsubcuenta = $subcuentaModel->idsubcuenta;
                return true;
            }
        }

        return false;
    }
}
