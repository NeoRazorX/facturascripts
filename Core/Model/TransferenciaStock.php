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
 * Description of transferencia_stock
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class TransferenciaStock extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key. integer
     *
     * @var int
     */
    public $idtrans;

    /**
     * Código de almacén de destino
     *
     * @var string
     */
    public $codalmadestino;

    /**
     * Código de almacén de origen
     *
     * @var string
     */
    public $codalmaorigen;

    /**
     * Fecha de la transferencia
     *
     * @var string
     */
    public $fecha;

    /**
     * Hora de la transferencia
     *
     * @var string
     */
    public $hora;

    /**
     * Usuario que realiza la transferencia
     *
     * @var string
     */
    public $usuario;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'transstock';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idtrans';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->codalmadestino === $this->codalmaorigen) {
            self::$miniLog->alert(self::$i18n->trans('warehouse-cant-be-same'));

            return false;
        }

        return true;
    }
}
