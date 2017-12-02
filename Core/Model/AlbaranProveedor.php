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
 * Albarán de proveedor o albarán de compra. Representa la recepción
 * de un material que se ha comprado. Implica la entrada de ese material
 * al almacén.
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
     * ID de la factura relacionada, si la hay.
     *
     * @var int
     */
    public $idfactura;

    /**
     * True => está pendiente de factura.
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
     * Returns the name of the column that is the primary key of the model.
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
     * Devuelve las líneas asociadas al albarán
     *
     * @return LineaAlbaranProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranProveedor();
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
     * Ejecuta un test completo de pruebas
     *
     * @return bool
     */
    public function fullTest()
    {
        return $this->fullTestTrait('albaran');
    }

    /**
     * Elimina el albarán de la base de datos
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName()
            . ' WHERE idalbaran = ' . $this->dataBase->var2str($this->idalbaran) . ';';
        if ($this->dataBase->exec($sql)) {
            if ($this->idfactura) {
                /**
                 * Delegamos la eliminación de la factura en la clase correspondiente,
                 * que tendrá que hacer más cosas.
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
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
        /**
         * Ponemos a null todos los idfactura que no están en facturasprov
         */
        $sql = 'UPDATE ' . static::tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturasprov);';
        $this->dataBase->exec($sql);
    }
}
