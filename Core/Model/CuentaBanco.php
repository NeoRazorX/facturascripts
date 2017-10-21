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

namespace FacturaScripts\Core\Model;

/**
 * Una cuenta bancaria de la propia empresa.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBanco
{
    use Base\ModelTrait;
    use Base\BankAccount;

    /**
     * Clave primaria. Varchar (6).
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Descripción de la cuenta
     *
     * @var string
     */
    public $descripcion;

    /**
     * Código de la subcuenta de contabilidad
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Devuelve el nombdre de la tabla que usa este modelo.
     *
     * @return string
     */
    public function tableName()
    {
        return 'cuentasbanco';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'cocuenta';
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return boolean
     */
    public function test()
    {
        if (!$this->testBankAccount()) {
            $this->miniLog->alert($this->i18n->trans('error-incorrect-bank-details'));

            return false;
        }

        return true;
    }
}
