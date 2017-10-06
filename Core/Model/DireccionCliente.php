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
 * Una dirección de un cliente. Puede tener varias.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class DireccionCliente
{
    use Base\ModelTrait;
    use Base\Direccion;

    /**
     * Clave primaria.
     *
     * @var integer
     */
    public $id;

    /**
     * Código del cliente asociado.
     *
     * @var string
     */
    public $codcliente;

    /**
     * TRUE -> esta dirección es la principal para envíos.
     *
     * @var
     */
    public $domenvio;

    /**
     * TRUE -> esta dirección es la principal para facturación.
     *
     * @var
     */
    public $domfacturacion;

    public function tableName()
    {
        return 'dirclientes';
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
        $this->codcliente = null;
        $this->codpais = null;
        $this->apartado = null;
        $this->provincia = null;
        $this->ciudad = null;
        $this->codpostal = null;
        $this->direccion = null;
        $this->domenvio = true;
        $this->domfacturacion = true;
        $this->descripcion = 'Principal';
        $this->fecha = date('d-m-Y');
    }
    
    public function test()
    {
        return $this->testDireccion();
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        /// actualizamos la fecha de modificación
        $this->fecha = date('d-m-Y');

        if ($this->test()) {
            if ($this->exists()) {
                /// ¿Desmarcamos las demás direcciones principales?
                $sql = '';
                if ($this->domenvio) {
                    $sql .= 'UPDATE ' . $this->tableName() . ' SET domenvio = false'
                        . ' WHERE codcliente = ' . $this->var2str($this->codcliente) . ';';
                }
                if ($this->domfacturacion) {
                    $sql .= 'UPDATE ' . $this->tableName() . ' SET domfacturacion = false'
                        . ' WHERE codcliente = ' . $this->var2str($this->codcliente) . ';';
                }
                $this->dataBase->exec($sql);

                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }
}
