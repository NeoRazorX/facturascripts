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
 * Delivery note or purchase order. Represents the reception
 * of a material that has been purchased. It implies the entry of that material
 * to the warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranProveedor
{

    use Base\DocumentoCompra;

    /**
     * Primary key. Integer
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
        return 'albaranesprov';
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
        /// nos aseguramos de que se comprueban las tablas de facturas y series antes
        new Serie();
        new FacturaProveedor();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearDocumentoCompra();
        $this->ptefactura = true;
    }

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaranProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranProveedor();
        return $lineaModel->all([new DataBaseWhere('idalbaran', $this->idalbaran)]);
    }

    /**
     * Check the delivery note data, return True if it is correct.
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }

    /**
     * Run a complete test of tests.
     *
     * @return bool
     */
    public function fullTest()
    {
        return $this->fullTestTrait('albaran');
    }

    /**
     * Remove the delivery note from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE idalbaran = ' . self::$dataBase->var2str($this->idalbaran) . ';';
        if (self::$dataBase->exec($sql)) {
            if ($this->idfactura) {
                /**
                 * We delegate the elimination of the invoice in the corresponding class,
                 * You will have to do more things.
                 */
                $factura = new FacturaProveedor();
                $factura0 = $factura->get($this->idfactura);
                if ($factura0) {
                    $factura0->delete();
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        /**
         * We put to null all the invoices that are not in facturasprov
         */
        $sql = 'UPDATE ' . static::tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturasprov);';
        self::$dataBase->exec($sql);
    }
}
