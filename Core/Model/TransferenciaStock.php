<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2016-2017    Carlos Garcia Gomez  <carlos@facturascripts.com>
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
class TransferenciaStock
{

    use Base\ModelTrait;

    /**
     * Clave primaria. integer
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
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'transstock';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idtrans';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idtrans = null;
        $this->codalmadestino = null;
        $this->codalmaorigen = null;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->usuario = null;
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->codalmadestino === $this->codalmaorigen) {
            $this->miniLog->alert($this->i18n->trans('warehouse-cant-be-same'));

            return false;
        }

        return true;
    }
}
