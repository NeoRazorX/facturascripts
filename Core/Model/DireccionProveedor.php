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
 * An address of a provider. It can have several.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DireccionProveedor extends Base\Address
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $id;

    /**
     * Code of the associated provider.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Description of the address.
     *
     * @var string
     */
    public $descripcion;

    /**
     * True -> main address.
     *
     * @var boolean
     */
    public $direccionppal;

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
        return 'dirproveedores';
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
        $this->direccionppal = true;
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
            if ($this->direccionppal) {
                $sql = 'UPDATE ' . static::tableName() . ' SET direccionppal = false'
                    . ' WHERE codproveedor = ' . self::$dataBase->var2str($this->codproveedor)
                    . ' AND id != ' . self::$dataBase->var2str($this->id) . ';';
                return self::$dataBase->exec($sql);
            }

            return true;
        }

        return false;
    }
}
