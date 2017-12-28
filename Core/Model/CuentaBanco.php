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
 * A bank account of the company itself.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBanco
{

    use Base\ModelTrait {
        url as private traitUrl;
    }

    use Base\BankAccount;

    /**
     * Primary key. Varchar (6).
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Code of the accounting sub-account.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentasbanco';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcuenta';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return boolean
     */
    public function test()
    {
        if (!$this->testBankAccount()) {
            ///self::$miniLog->alert(self::$i18n->trans('error-incorrect-bank-details'));

            return false;
        }

        return true;
    }

    /**
     * Returns the url where to see/modify the data..
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitUrl($type, 'ListFormaPago&active=List');
    }
}
