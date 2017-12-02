<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Regularización del stock de un almacén de un artículos en una fecha concreta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RegularizacionStock
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * ID del stock, en el modelo stock.
     *
     * @var int
     */
    public $idstock;

    /**
     * Cantidad inicial
     *
     * @var float|int
     */
    public $cantidadini;

    /**
     * Cantidad final
     *
     * @var float|int
     */
    public $cantidadfin;

    /**
     * Código del almacén destino.
     *
     * @var string
     */
    public $codalmacendest;

    /**
     * Fecha
     *
     * @var string
     */
    public $fecha;

    /**
     * Hora
     *
     * @var string
     */
    public $hora;

    /**
     * Motivo de la regularización
     *
     * @var string
     */
    public $motivo;

    /**
     * Nick del usuario que ha realizado la regularización.
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
        $this->id = null;
        $this->idstock = null;
        $this->cantidadini = 0;
        $this->cantidadfin = 0;
        $this->codalmacendest = null;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->motivo = '';
        $this->nick = null;
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
