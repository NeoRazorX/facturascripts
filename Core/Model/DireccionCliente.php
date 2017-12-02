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
 * Una dirección de un cliente. Puede tener varias.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DireccionCliente
{

    use Base\ModelTrait,
        Base\Direccion {
        clear as private traitClear;
    }

    /**
     * Primary key.
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'dirclientes';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
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
        $this->traitClear();

        $this->domenvio = true;
        $this->domfacturacion = true;
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
     * Persiste los datos en la base de datos, modificando si existía el registro
     * o insertando en caso de no existir la clave primaria.
     *
     * @return bool
     */
    private function saveData()
    {
        if ($this->exists()) {
            return $this->saveUpdate();
        }

        return $this->saveInsert();
    }

    /**
     * Store the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        /// actualizamos la fecha de modificación
        $this->fecha = date('d-m-Y');

        if ($this->test()) {
            /// ¿Desmarcamos las demás direcciones principales?
            $sql = '';
            $where = 'WHERE codcliente = ' . $this->dataBase->var2str($this->codcliente);
            if ($this->domenvio) {
                $sql .= 'UPDATE ' . static::tableName() . ' SET domenvio = false ' . $where . ' AND domenvio = TRUE;';
            }
            if ($this->domfacturacion) {
                $sql .= 'UPDATE ' . static::tableName() . ' SET domfacturacion = false '
                    . $where . ' AND domfacturacion = TRUE;';
            }

            if (empty($sql)) {
                return $this->saveData();
            }

            $this->dataBase->beginTransaction();
            if ($this->dataBase->exec($sql)) {
                return $this->saveData() ? $this->dataBase->commit() : $this->dataBase->rollback();
            }
        }

        return false;
    }
}
