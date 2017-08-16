<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Una dirección de un proveedor. Puede tener varias.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class DireccionProveedor
{

    use Base\ModelTrait;
    use Base\Direccion;

    /**
     * Clave primaria.
     * @var integer
     */
    public $id;

    /**
     * Código del proveedor asociado.
     * @var string
     */
    public $codproveedor;

    /**
     * TRUE -> dirección principal
     * @var boolean
     */
    public $direccionppal;

    public function tableName()
    {
        return 'dirproveedores';
    }

    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
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
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        /// actualizamos la fecha de modificación
        $this->fecha = date('d-m-Y');

        if ($this->test()) {
            /// ¿Desmarcamos las demás direcciones principales?
            if ($this->direccionppal) {
                $sql = 'UPDATE ' . $this->tableName() . ' SET direccionppal = false'
                    . ' WHERE codproveedor = ' . $this->var2str($this->codproveedor) . ';';
                $this->dataBase->exec($sql);
            }

            if ($this->exists()) {
                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }
}
