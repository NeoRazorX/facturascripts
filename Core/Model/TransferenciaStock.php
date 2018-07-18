<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

/**
 * The head of transfer.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class TransferenciaStock extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key autoincremental.
     *
     * @var int
     */
    public $idtransferhead;

    /**
     * Warehouse of origin. Varchar (4).
     *
     * @var string
     */
    public $codalmacenorigen;

    /**
     * Warehouse of destination. Varchar (4).
     *
     * @var string
     */
    public $codalmacendestino;

    /**
     * Date of transfer.
     *
     * @var string
     */
    public $fecha;

    /**
     * User of transfer action. Varchar (50).
     *
     * @var string
     */
    public $usuario;

    /**
     * 
     * @return string
     */
    public function install()
    {
        new LineaTransferenciaStock();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idtransferhead';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'transferenciasstock';
    }
}
