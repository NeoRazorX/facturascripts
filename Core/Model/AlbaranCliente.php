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
 * from the company warehouse.
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
     * This function is called when creating the model table. Return the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// we force the checking of the invoice table.
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
     * Returns True if there is no erros on properties values.
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
         * Ponemos a Null todos los idfactura que no están en facturascli.
         * ¿Por qué? Porque muchos usuarios se dedican a tocar la base de datos.
         */
        $this->dataBase->exec('UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturascli);');
    }
}
