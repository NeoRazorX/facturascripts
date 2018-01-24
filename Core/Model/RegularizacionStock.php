<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Regularization of the stock of a warehouse of articles on a specific date.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RegularizacionStock extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Stock ID, in the stock model.
     *
     * @var int
     */
    public $idstock;

    /**
     * Initial amount.
     *
     * @var float|int
     */
    public $cantidadini;

    /**
     * Final amount.
     *
     * @var float|int
     */
    public $cantidadfin;

    /**
     * Destination warehouse code.
     *
     * @var string
     */
    public $codalmacendest;

    /**
     * Date.
     *
     * @var string
     */
    public $fecha;

    /**
     * Time.
     *
     * @var string
     */
    public $hora;

    /**
     * Reason of the regularization.
     *
     * @var string
     */
    public $motivo;

    /**
     * Nick of the user who has done the regularization.
     *
     * @var string
     */
    public $nick;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasregstocks';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidadini = 0;
        $this->cantidadfin = 0;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->motivo = '';
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
        new Stock();

        return '';
    }
}
