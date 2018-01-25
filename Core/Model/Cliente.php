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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * The client. You can have one or more associated addresses and sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\ComercialContact
{

    use Base\ModelTrait;

    /**
     * Employee assigned to this customer. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Group to which the client belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * True -> equivalence surcharge is applied to the client.
     *
     * @var boolean
     */
    public $recargo;

    /**
     * Preferred payment days when calculating the due date of invoices.
     * Days separated by commas: 1,15,31
     *
     * @var string
     */
    public $diaspago;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'clientes';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codcliente';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// we need to check model GrupoClientes before
        new GrupoClientes();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->recargo = false;
    }

    /**
     * Returns an array with the addresses associated with the client.
     *
     * @return DireccionCliente[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionCliente();

        return $dirModel->all([new DataBaseWhere('codcliente', $this->codcliente)]);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        parent::test();
        $this->codcliente = empty($this->codcliente) ? (string) $this->newCode() : trim($this->codcliente);

        /// we validate the days of payment
        $arrayDias = [];
        foreach (str_getcsv($this->diaspago) as $d) {
            if ((int) $d >= 1 && (int) $d <= 31) {
                $arrayDias[] = (int) $d;
            }
        }
        $this->diaspago = null;
        if (!empty($arrayDias)) {
            $this->diaspago = implode(',', $arrayDias);
        }

        return true;
    }
}
