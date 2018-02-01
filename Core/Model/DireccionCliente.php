<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * An address of a client. It can have several.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DireccionCliente extends Base\Address
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $id;

    /**
     * Code of the associated customer.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Description of the address.
     *
     * @var string
     */
    public $descripcion;

    /**
     * True -> this address is the main one for shipments.
     *
     * @var bool
     */
    public $domenvio;

    /**
     * True -> this address is the main one for billing.
     *
     * @var bool
     */
    public $domfacturacion;

    /**
     * Date of last modification.
     *
     * @var string
     */
    public $fecha;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'dirclientes';
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
        $this->descripcion = 'Principal';
        $this->domenvio = true;
        $this->domfacturacion = true;
        $this->fecha = date('d-m-Y');
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        /// update the modification date
        $this->fecha = date('d-m-Y');

        if (parent::save()) {
            /// Do we demarcate the other main directions?
            $sql = '';
            if ($this->domenvio) {
                $sql .= 'UPDATE ' . static::tableName() . ' SET domenvio = false'
                    . ' WHERE codcliente = ' . self::$dataBase->var2str($this->codcliente)
                    . ' AND id != ' . self::$dataBase->var2str($this->id) . ';';
            }
            if ($this->domfacturacion) {
                $sql .= 'UPDATE ' . static::tableName() . ' SET domfacturacion = false'
                    . ' WHERE codcliente = ' . self::$dataBase->var2str($this->codcliente)
                    . ' AND id != ' . self::$dataBase->var2str($this->id) . ';';
            }

            return empty($sql) ? true : self::$dataBase->exec($sql);
        }

        return false;
    }
}
