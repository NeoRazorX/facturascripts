<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Dinamic\Model\Cliente as DinCliente;

/**
 * A bank account of a client.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBancoCliente extends Base\BankAccount
{

    use Base\ModelTrait;

    /**
     * Customer code.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Date on which the mandate to authorize the direct debit of receipts was signed.
     *
     * @var string
     */
    public $fmandato;

    /**
     * Is it the customer's main account?
     *
     * @var bool
     */
    public $principal;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fmandato = \date(self::DATE_STYLE);
        $this->principal = true;
    }

    /**
     * 
     * @return DinCliente
     */
    public function getSubject()
    {
        $customer = new DinCliente();
        $customer->loadFromCode($this->codcliente);
        return $customer;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new DinCliente();

        return parent::install();
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (parent::save()) {
            $this->updatePrimaryAccount();
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentasbcocli';
    }

    protected function updatePrimaryAccount()
    {
        if ($this->principal) {
            /// If this account is the main one, we demarcate the others
            $sql = 'UPDATE ' . static::tableName()
                . ' SET principal = false'
                . ' WHERE codcliente = ' . self::$dataBase->var2str($this->codcliente)
                . ' AND codcuenta != ' . self::$dataBase->var2str($this->codcuenta) . ';';
            self::$dataBase->exec($sql);
        }
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->codcliente) || $type == 'list' ? parent::url($type, $list) : $this->getSubject()->url();
    }
}
