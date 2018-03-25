<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2018    Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of linea_transferencia_stock
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaTransferenciaStock extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idlinea;

    /**
     * Transfer identifier.
     *
     * @var int
     */
    public $idtrans;

    /**
     * Reference of the main product.
     *
     * @var string
     */
    public $referencia;

    /**
     * Reference of the combination, if exists. Null if not exist.
     *
     * @var string
     */
    public $codigo;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Description of the transfer.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineastransstock';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->descripcion = '';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->cantidad <= 0) {
            self::$miniLog->alert(self::$i18n->trans('quantity-not-0'));

            return false;
        }

        return true;
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
        /// we force the check of the stock transfers table
        //new TransferenciaStock();

        return '';
    }
}
