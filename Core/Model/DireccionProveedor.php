<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
class DireccionProveedor
{

    use Base\ModelTrait;
    use Base\Direccion;

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
     * True -> main address.
     *
     * @var boolean
     */
    public $direccionppal;

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
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->id = null;
        $this->codproveedor = null;
        $this->codpais = null;
        $this->apartado = null;
        $this->provincia = null;
        $this->ciudad = null;
        $this->codpostal = null;
        $this->direccion = null;
        $this->direccionppal = true;
        $this->descripcion = 'Principal';
        $this->fecha = date('d-m-Y');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        return $this->testDireccion();
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

        if ($this->test()) {
            /// Do we demarcate the other main directions?
            if ($this->direccionppal) {
                $sql = 'UPDATE ' . static::tableName() . ' SET direccionppal = false'
                    . ' WHERE codproveedor = ' . self::$dataBase->var2str($this->codproveedor) . ';';
                self::$dataBase->exec($sql);
            }

            if ($this->exists()) {
                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }
}
