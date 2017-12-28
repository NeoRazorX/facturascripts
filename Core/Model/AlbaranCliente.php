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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Customer's delivery note or delivery note. Represents delivery to a customer
 * of a material that has been sold to you. It implies the exit of this material
 * from the company's warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranCliente
{

    use Base\DocumentoVenta;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * ID of the related invoice, if any.
     *
     * @var int
     */
    public $idfactura;

    /**
     * True => is pending invoice.
     *
     * @var bool
     */
    public $ptefactura;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'albaranescli';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idalbaran';
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
        /// we force the checking of the bill tablecli.
        new FacturaCliente();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearDocumentoVenta();
        $this->ptefactura = true;
    }

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaranCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranCliente();
        return $lineaModel->all([new DataBaseWhere('idalbaran', $this->idalbaran)]);
    }

    /**
     * Check the data of the delivery note, return True if they are correct.
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        /**
         * We put to Null all the invoices that are not in invoices.
         * Why? Because many users are dedicated to touching the database.
         */
        self::$dataBase->exec('UPDATE ' . static::tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturascli);');
    }
}
