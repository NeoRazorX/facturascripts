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
 * Delivery note or purchase order. Represents the reception
 * of a material that has been purchased. It implies the entry of that material
 * to the warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranProveedor extends Base\PurchaseDocument
{
    use Base\ModelTrait;

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
    public static function primaryColumn()
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
        parent::install();
        new FacturaProveedor();

        return '';
    }

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaranProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranProveedor();
        $where = [new DataBaseWhere('idalbaran', $this->idalbaran)];

        return $lineaModel->all($where, [], 0, 0);
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
}
