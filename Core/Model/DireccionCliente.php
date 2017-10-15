<?php
/**
 * This file is part of facturacion_base
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
 * Una dirección de un cliente. Puede tener varias.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DireccionCliente
{

    use Base\ModelTrait,
        Base\Direccion {

        save as private traitSave;
        clear as private traitClear;
    }

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
     * @var bool
     */
    public $domenvio;

    /**
     * TRUE -> esta dirección es la principal para facturación.
     *
     * @var bool
     */
    public $domfacturacion;

    /**
     * Devuelve el nombdre de la tabla que usa este modelo.
     *
     * @return string
     */
    public function tableName()
    {
        return 'dirclientes';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->traitClear();

        $this->domenvio = true;
        $this->domfacturacion = true;
        $this->descripcion = 'Principal';
        $this->fecha = date('d-m-Y');
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
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
                $where = 'WHERE codcliente = ' . $this->var2str($this->codcliente);
                if ($this->domenvio) {
                    $sql .= 'UPDATE ' . $this->tableName() . ' SET domenvio = false ' . $where . ' AND domenvio = TRUE;';
                }
                if ($this->domfacturacion) {
                    $sql .= 'UPDATE ' . $this->tableName() . ' SET domfacturacion = false ' . $where . ' AND domfacturacion = TRUE;';
                }

                // Sólo actualizamos el registro de dirección
                if (empty($sql)) {
                    return $this->saveUpdate();
                }
                
                // Actualizamos el registro de dirección y activamos como dirección principal
                $this->dataBase->beginTransaction();
                $allOK = $this->dataBase->exec($sql);
                if ($allOK) {
                    $allOK = $this->saveUpdate() ? $this->dataBase->commit() : $this->dataBase->rollback();
                }
                return $allOK;
            }

            return $this->saveInsert();
        }

        return false;
    }
}
